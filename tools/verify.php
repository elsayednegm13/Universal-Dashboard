<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$root = dirname(__DIR__);
require_once $root . '/includes/module-registry.php';
require_once $root . '/includes/version.php';

$errors = [];
$warnings = [];
$checks = 0;
$check = static function (bool $ok, string $message, bool $warning = false) use (&$errors, &$warnings, &$checks): void {
    $checks++;
    if (!$ok) { if ($warning) $warnings[] = $message; else $errors[] = $message; }
};

$required = [
    'README.md','VERSION','.gitignore','.htaccess','Makefile','Dockerfile','docker-compose.yml','.env.example','.env.docker.example','docker/entrypoint.sh',
    '.github/workflows/ci.yml','database/core.sql','includes/database.php','includes/module-registry.php','includes/migrations.php',
    'tools/generate-module.php','tools/migrate.php','tools/doctor.php','tools/release-check.php',
    'tests/integration/http-installer.sh','tests/integration/runtime.php','tests/integration/upgrade-v0_3.php','tests/integration/generator.php',
];
foreach ($required as $file) $check(is_file($root . '/' . $file), 'Missing required release file: ' . $file);

try {
    $modules = cmsAvailableModules(true);
    $check((bool)$modules, 'No module manifests discovered.');
    $adminPaths = [];
    $apiPaths = [];
    $tableOwners = [];
    foreach ($modules as $key => $manifest) {
        $check(is_file($manifest['path'] . '/module.php'), "{$key}: module.php missing");
        $check((bool)preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/', (string)$manifest['version']), "{$key}: invalid semantic version");
        if (!empty($manifest['admin'])) {
            $check(is_file($manifest['path'] . '/admin.php'), "{$key}: module admin.php missing");
            $wrapper = $root . (string)$manifest['admin'];
            $check(is_file($wrapper), "{$key}: admin wrapper missing: {$manifest['admin']}");
            if (isset($adminPaths[$manifest['admin']])) $errors[] = "Duplicate admin route: {$manifest['admin']}";
            $adminPaths[$manifest['admin']] = $key;
        }
        if (!empty($manifest['api'])) {
            $check(is_file($manifest['path'] . '/api.php'), "{$key}: module api.php missing");
            $wrapper = $root . (string)$manifest['api'];
            $check(is_file($wrapper), "{$key}: api wrapper missing: {$manifest['api']}");
            if (isset($apiPaths[$manifest['api']])) $errors[] = "Duplicate API route: {$manifest['api']}";
            $apiPaths[$manifest['api']] = $key;
        }
        foreach ($manifest['tables'] as $table) {
            if (isset($tableOwners[$table]) && $table !== 'audit_logs') $errors[] = "Table declared by multiple modules: {$table}";
            $tableOwners[$table] = $key;
        }
        foreach (cmsMigrationFiles($manifest['path'] . '/migrations') as $migration) {
            $keyName = basename($migration);
            $check((bool)preg_match('/^\d{3,}_[A-Za-z0-9_.-]+\.sql$/', $keyName), "{$key}: invalid migration filename {$keyName}");
            $raw = file_get_contents($migration);
            $check($raw !== false && trim((string)$raw) !== '', "{$key}: empty/unreadable migration: {$keyName}");
        }
        foreach ($manifest['depends'] as $dependency) $check(isset($modules[$dependency]), "{$key}: missing dependency {$dependency}");
    }
    foreach (array_keys($modules) as $key) {
        try { cmsResolveModuleDependencies($key); $check(true, "{$key}: dependencies"); }
        catch (Throwable $e) { $check(false, $e->getMessage()); }
    }
} catch (Throwable $e) {
    $modules = [];
    $errors[] = $e->getMessage();
}

foreach (cmsMigrationFiles($root . '/database/migrations') as $migration) {
    $name = basename($migration);
    $check((bool)preg_match('/^\d{3,}_[A-Za-z0-9_.-]+\.sql$/', $name), 'Invalid core migration filename: ' . $name);
    $raw = file_get_contents($migration);
    $check($raw !== false && trim((string)$raw) !== '', 'Empty core migration: ' . $name);
}

foreach (['storage/config.local.php','storage/install.lock','.env'] as $runtimeSecret) {
    $check(!is_file($root . '/' . $runtimeSecret), "Runtime file should not exist in distributable package: {$runtimeSecret}");
}

$gitignore = is_file($root . '/.gitignore') ? (string)file_get_contents($root . '/.gitignore') : '';
foreach (['/storage/config.local.php','/storage/install.lock','/storage/backups/*','/uploads/*','.env'] as $needle) {
    $check(str_contains($gitignore, $needle), '.gitignore missing protection: ' . $needle);
}

$forbiddenNames = ['fa'.'hhad','fa'.'had'];
$scanExtensions = ['php','md','sql','json','yml','yaml','js','css','html'];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $rel = str_replace('\\','/',substr($file->getPathname(), strlen($root)+1));
    if (str_starts_with($rel, '.git/')) continue;
    if (!in_array(strtolower($file->getExtension()), $scanExtensions, true)) continue;
    $content = file_get_contents($file->getPathname());
    if ($content === false) continue;
    foreach ($forbiddenNames as $name) {
        if (stripos($content, $name) !== false) $errors[] = "Project-specific name '{$name}' found in {$rel}";
    }
}

$check(UNIVERSAL_CMS_VERSION === trim((string)file_get_contents($root . '/VERSION')), 'VERSION file and includes/version.php mismatch');

if ($warnings) {
    fwrite(STDOUT, "VERIFY WARNINGS\n");
    foreach ($warnings as $warning) fwrite(STDOUT, "- {$warning}\n");
}
if ($errors) {
    fwrite(STDERR, "VERIFY FAILED\n");
    foreach (array_values(array_unique($errors)) as $error) fwrite(STDERR, "- {$error}\n");
    exit(1);
}

fwrite(STDOUT, 'VERIFY PASS version=' . UNIVERSAL_CMS_VERSION . ' modules=' . count($modules ?? []) . ' checks=' . $checks . PHP_EOL);
