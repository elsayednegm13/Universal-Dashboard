<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(2);
$root = dirname(__DIR__, 2);
require_once $root . '/includes/database.php';
require_once $root . '/includes/module-registry.php';

function uassert(bool $ok, string $message): void { if (!$ok) throw new RuntimeException('ASSERT: ' . $message); }
function usql(PDO $pdo, string $path): void {
    $raw = file_get_contents($path); if ($raw === false) throw new RuntimeException('read failed');
    foreach (cmsMigrationStatements(cmsMigrationNormalizedContent($raw)) as $statement) $pdo->exec($statement);
}

$cfg = cmsConfig()['db'];
if (!preg_match('/(?:test|ci)/i', (string)$cfg['name'])) { fwrite(STDERR, "Refusing upgrade test outside test/ci DB.\n"); exit(3); }
$pdo = cmsPdo();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) if (preg_match('/^[A-Za-z0-9_]+$/',(string)$table)) $pdo->exec('DROP TABLE `'.$table.'`');
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

// v0.3 baseline: core schema + only the U3 core migration recorded.
usql($pdo, $root . '/tests/fixtures/v0_3_core.sql');
$raw = file_get_contents($root . '/database/migrations/001_u3_migration_engine.sql');
$checksum = hash('sha256', cmsMigrationNormalizedContent((string)$raw));
$stmt = $pdo->prepare('INSERT INTO cms_schema_migrations(scope_key,migration_key,checksum) VALUES(?,?,?)');
$stmt->execute(['core','001_u3_migration_engine.sql',$checksum]);
uassert(!cmsTableExistsForUpgrade($pdo, 'cms_release_history'), 'release table absent on v0.3 baseline');

$ran = cmsRunCoreMigrations($pdo);
uassert(in_array('002_u4_release_history.sql', $ran, true), 'U4 migration applied');
uassert(cmsTableExistsForUpgrade($pdo, 'cms_release_history'), 'release table created');
uassert((string)$pdo->query("SELECT version FROM cms_release_history WHERE version='0.4.0'")->fetchColumn() === '0.4.0', 'release history row recorded');
$status = cmsMigrationStatus($pdo, 'core', $root . '/database/migrations');
uassert(!$status['pending'] && !$status['drift'], 'post-upgrade migration state clean');
echo "UPGRADE v0.3 -> v0.4 PASS\n";

function cmsTableExistsForUpgrade(PDO $pdo, string $table): bool {
    $q=$pdo->prepare('SHOW TABLES LIKE ?');$q->execute([$table]);return (bool)$q->fetchColumn();
}
