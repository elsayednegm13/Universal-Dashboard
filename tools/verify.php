<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/includes/module-registry.php';

$errors = [];
$checks = 0;

try {
    $modules = cmsAvailableModules(true);
    if (!$modules) {
        $errors[] = 'No module manifests discovered.';
    }
    foreach ($modules as $key => $manifest) {
        $checks++;
        if (!is_file($manifest['path'] . '/module.php')) {
            $errors[] = "{$key}: module.php missing";
        }
        if (!is_file($manifest['path'] . '/admin.php') && !empty($manifest['admin'])) {
            $errors[] = "{$key}: module admin.php missing";
        }
        if (!empty($manifest['admin'])) {
            $wrapper = $root . (string)$manifest['admin'];
            if (!is_file($wrapper)) {
                $errors[] = "{$key}: admin wrapper missing: {$manifest['admin']}";
            }
        }
        if (!empty($manifest['api'])) {
            if (!is_file($manifest['path'] . '/api.php')) {
                $errors[] = "{$key}: module api.php missing";
            }
            $wrapper = $root . (string)$manifest['api'];
            if (!is_file($wrapper)) {
                $errors[] = "{$key}: api wrapper missing: {$manifest['api']}";
            }
        }
        foreach (cmsMigrationFiles($manifest['path'] . '/migrations') as $migration) {
            $checks++;
            $raw = file_get_contents($migration);
            if ($raw === false || trim($raw) === '') {
                $errors[] = "{$key}: empty/unreadable migration: " . basename($migration);
            }
        }
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

foreach (['storage/config.local.php', 'storage/install.lock'] as $runtimeSecret) {
    $checks++;
    if (is_file($root . '/' . $runtimeSecret)) {
        $errors[] = "Runtime file should not exist in distributable package: {$runtimeSecret}";
    }
}

if ($errors) {
    fwrite(STDERR, "VERIFY FAILED\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, 'VERIFY PASS — modules=' . count($modules ?? []) . ' checks=' . $checks . PHP_EOL);
