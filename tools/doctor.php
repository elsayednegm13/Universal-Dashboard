<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$staticOnly = in_array('--static', $argv, true);
require_once $root . '/includes/config.php';
require_once $root . '/includes/module-registry.php';
require_once $root . '/includes/version.php';

$checks = [];
$errors = [];
$warnings = [];

$add = static function (string $name, bool $ok, string $detail = '', bool $warning = false) use (&$checks, &$errors, &$warnings): void {
    $checks[] = [$name, $ok, $detail, $warning];
    if (!$ok) {
        if ($warning) $warnings[] = $name . ($detail !== '' ? ': ' . $detail : ''); else $errors[] = $name . ($detail !== '' ? ': ' . $detail : '');
    }
};

$add('PHP >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>='), PHP_VERSION);
foreach (['PDO','mbstring','fileinfo','zlib'] as $ext) {
    $add('extension ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'loaded' : 'missing');
}
$add('extension pdo_mysql', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'loaded' : 'missing', $staticOnly);
$add('storage writable', is_dir($root . '/storage') && is_writable($root . '/storage'), $root . '/storage');
$add('uploads writable', is_dir($root . '/uploads') && is_writable($root . '/uploads'), $root . '/uploads');
$add('backup directory writable', is_dir($root . '/storage/backups') && is_writable($root . '/storage/backups'), $root . '/storage/backups');

try {
    $modules = cmsAvailableModules(true);
    $add('module manifests', count($modules) > 0, count($modules) . ' discovered');
} catch (Throwable $e) {
    $modules = [];
    $add('module manifests', false, $e->getMessage());
}

if (!$staticOnly) {
    try {
        require_once $root . '/includes/database.php';
        $pdo = cmsPdo();
        $dbVersion = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
        $add('database connection', true, $dbVersion);
        $add('core table admin_users', (bool)$pdo->query("SHOW TABLES LIKE 'admin_users'")->fetchColumn());
        $add('core table cms_schema_migrations', (bool)$pdo->query("SHOW TABLES LIKE 'cms_schema_migrations'")->fetchColumn());
        $hasReleaseHistory = (bool)$pdo->query("SHOW TABLES LIKE 'cms_release_history'")->fetchColumn();
        $add('core table cms_release_history', $hasReleaseHistory, $hasReleaseHistory ? 'present' : 'missing', true);
        if ($hasReleaseHistory) {
            $release = (string)$pdo->query('SELECT version FROM cms_release_history ORDER BY installed_at DESC,id DESC LIMIT 1')->fetchColumn();
            $add('release history current', $release === UNIVERSAL_CMS_VERSION, $release !== '' ? $release : 'empty', true);
        }
        $core = cmsMigrationStatus($pdo, 'core', $root . '/database/migrations');
        $add('core migration drift', empty($core['drift']), $core['drift'] ? implode(',', $core['drift']) : 'none');
        $add('core pending migrations', empty($core['pending']), $core['pending'] ? implode(',', $core['pending']) : 'none', true);
        if ((bool)$pdo->query("SHOW TABLES LIKE 'cms_modules'")->fetchColumn()) {
            $states = cmsModuleStates($pdo);
            foreach ($modules as $key => $manifest) {
                $status = cmsModuleMigrationStatus($pdo, $key);
                $add("module {$key} drift", empty($status['drift']), $status['drift'] ? implode(',', $status['drift']) : 'none');
                if (($states[$key] ?? false) && $status['pending']) {
                    $add("module {$key} pending", false, implode(',', $status['pending']), true);
                }
            }
        }
    } catch (Throwable $e) {
        $add('database connection', false, $e->getMessage());
    }
}

fwrite(STDOUT, "Universal CMS Doctor v" . UNIVERSAL_CMS_VERSION . PHP_EOL);
foreach ($checks as [$name, $ok, $detail, $warning]) {
    $status = $ok ? 'PASS' : ($warning ? 'WARN' : 'FAIL');
    fwrite(STDOUT, sprintf("%-5s %-34s %s\n", $status, $name, $detail));
}
fwrite(STDOUT, 'summary: checks=' . count($checks) . ' warnings=' . count($warnings) . ' errors=' . count($errors) . PHP_EOL);
exit($errors ? 1 : 0);
