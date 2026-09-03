<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/module-registry.php';

$all = in_array('--all', $argv, true);
$statusOnly = in_array('--status', $argv, true);

try {
    $pdo = cmsPdo();
    $available = cmsAvailableModules();
    $states = cmsModuleStates($pdo);

    if ($statusOnly) {
        $core = cmsMigrationStatus($pdo, 'core', __DIR__ . '/../database/migrations');
        fwrite(STDOUT, 'core: pending=' . count($core['pending']) . ' drift=' . count($core['drift']) . PHP_EOL);
        foreach ($available as $key => $manifest) {
            $st = cmsModuleMigrationStatus($pdo, $key);
            $enabled = ($states[$key] ?? false) ? 'enabled' : 'disabled';
            fwrite(STDOUT, $key . ': ' . $enabled . ' pending=' . count($st['pending']) . ' drift=' . count($st['drift']) . PHP_EOL);
        }
        exit(0);
    }

    $executed = [];
    foreach (cmsRunCoreMigrations($pdo) as $migration) {
        $executed[] = 'core/' . $migration;
    }

    foreach ($available as $key => $manifest) {
        if (!$all && !($states[$key] ?? false)) {
            continue;
        }
        foreach (cmsRunModuleMigrations($pdo, $key) as $migration) {
            $executed[] = $migration;
        }
    }

    if (!$executed) {
        fwrite(STDOUT, "No pending migrations.\n");
        exit(0);
    }
    foreach ($executed as $migration) {
        fwrite(STDOUT, "APPLIED {$migration}\n");
    }
    fwrite(STDOUT, 'Applied ' . count($executed) . " migration(s).\n");
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
