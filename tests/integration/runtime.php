<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(2);
$root = dirname(__DIR__, 2);
require_once $root . '/includes/database.php';
require_once $root . '/includes/module-registry.php';
require_once $root . '/includes/backup.php';
require_once $root . '/includes/version.php';

function tassert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('ASSERT: ' . $message);
}
function runSqlFile(PDO $pdo, string $path): void {
    $raw = file_get_contents($path);
    if ($raw === false) throw new RuntimeException('Cannot read ' . $path);
    foreach (cmsMigrationStatements(cmsMigrationNormalizedContent($raw)) as $statement) $pdo->exec($statement);
}

$cfg = cmsConfig()['db'];
$name = (string)$cfg['name'];
if (!preg_match('/(?:test|ci)/i', $name)) {
    fwrite(STDERR, "Refusing integration suite: database name must contain 'test' or 'ci'.\n");
    exit(3);
}

$pdo = cmsPdo();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
    if (preg_match('/^[A-Za-z0-9_]+$/', (string)$table)) $pdo->exec('DROP TABLE `' . $table . '`');
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

runSqlFile($pdo, $root . '/database/core.sql');
$coreRan = cmsRunCoreMigrations($pdo);
$modules = cmsAvailableModules(true);
foreach (array_keys($modules) as $key) cmsRunModuleMigrations($pdo, $key);

$stmt = $pdo->prepare('INSERT INTO admin_users(name,email,password_hash,is_active) VALUES(?,?,?,1)');
$stmt->execute(['Runtime Admin','runtime@example.test',password_hash('Runtime-Password-123!', PASSWORD_DEFAULT)]);
$set = $pdo->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
$set->execute(['site_name','Universal CMS Runtime Test']);
$mod = $pdo->prepare('INSERT INTO cms_modules(module_key,is_enabled) VALUES(?,1) ON DUPLICATE KEY UPDATE is_enabled=1');
foreach (array_keys($modules) as $key) $mod->execute([$key]);

tassert((int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 1, 'admin user created');
tassert((int)$pdo->query('SELECT COUNT(*) FROM cms_schema_migrations')->fetchColumn() >= 11, 'migrations recorded');
tassert((string)$pdo->query("SELECT version FROM cms_release_history WHERE version='0.4.0'")->fetchColumn() === '0.4.0', 'release history recorded');
foreach ($modules as $key => $manifest) {
    foreach ($manifest['tables'] as $table) {
        $q = $pdo->prepare('SHOW TABLES LIKE ?');
        $q->execute([$table]);
        tassert((bool)$q->fetchColumn(), "module table exists {$key}/{$table}");
    }
}

// Drift detection without modifying repository files.
$tmp = sys_get_temp_dir() . '/cms-drift-' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, true);
$coreMigration = $root . '/database/migrations/001_u3_migration_engine.sql';
$driftFile = $tmp . '/001_u3_migration_engine.sql';
copy($coreMigration, $driftFile);
file_put_contents($driftFile, "\n-- intentional drift probe\n", FILE_APPEND);
$status = cmsMigrationStatus($pdo, 'core', $tmp);
tassert(in_array('001_u3_migration_engine.sql', $status['drift'], true), 'migration checksum drift detected');
unlink($driftFile); rmdir($tmp);

// Data + uploads backup/restore proof on disposable DB.
$probePath = $root . '/uploads/u4-runtime-probe.txt';
file_put_contents($probePath, 'before', LOCK_EX);
$set->execute(['u4_runtime_probe','before']);
$beforeBackups = glob(cmsBackupDir() . '/cms-*.cmsbak') ?: [];
$backupName = cmsCreateBackup($pdo, 'u4-runtime-test');
$summary = cmsBackupSummary(cmsBackupDir() . '/' . $backupName);
tassert(($summary['valid'] ?? false) === true && (int)($summary['backup_version'] ?? 0) === 3, 'backup v3 summary valid');
$set->execute(['u4_runtime_probe','after']);
file_put_contents($probePath, 'after', LOCK_EX);
cmsRestoreBackup($pdo, cmsBackupDir() . '/' . $backupName);
$read = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key=?');
$read->execute(['u4_runtime_probe']);
tassert($read->fetchColumn() === 'before', 'database value restored');
tassert(file_get_contents($probePath) === 'before', 'upload file restored');

// Module state behavior: disabling must not drop data/tables.
$pdo->prepare('UPDATE cms_modules SET is_enabled=0 WHERE module_key=?')->execute(['faq']);
tassert(!cmsModuleEnabled($pdo, 'faq'), 'FAQ disabled');
$q = $pdo->query("SHOW TABLES LIKE 'faqs'");
tassert((bool)$q->fetchColumn(), 'FAQ table retained after disable');
$pdo->prepare('UPDATE cms_modules SET is_enabled=1 WHERE module_key=?')->execute(['faq']);

@unlink($probePath);
foreach (glob(cmsBackupDir() . '/cms-*.cmsbak') ?: [] as $file) {
    if (!in_array($file, $beforeBackups, true)) @unlink($file);
}

$coreStatus = cmsMigrationStatus($pdo, 'core', $root . '/database/migrations');
tassert(!$coreStatus['pending'] && !$coreStatus['drift'], 'core migrations clean');
foreach (array_keys($modules) as $key) {
    $s = cmsModuleMigrationStatus($pdo, $key);
    tassert(!$s['pending'] && !$s['drift'], "module migrations clean {$key}");
}

echo 'RUNTIME PASS version=' . UNIVERSAL_CMS_VERSION . ' modules=' . count($modules) . ' migrations=' . $pdo->query('SELECT COUNT(*) FROM cms_schema_migrations')->fetchColumn() . PHP_EOL;
