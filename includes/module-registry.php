<?php

declare(strict_types=1);
require_once __DIR__ . '/migrations.php';

function cmsModuleRoot(): string
{
    return realpath(__DIR__ . '/../modules') ?: (__DIR__ . '/../modules');
}

function cmsNormalizeModuleManifest(array $manifest, string $directoryKey): array
{
    $key = (string)($manifest['key'] ?? '');
    if ($key !== $directoryKey || !preg_match('/^[a-z][a-z0-9_]{1,39}$/', $key)) {
        throw new RuntimeException('Invalid module manifest key: ' . $directoryKey);
    }

    $tables = [];
    foreach ((array)($manifest['tables'] ?? []) as $table) {
        $table = (string)$table;
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $table)) {
            throw new RuntimeException('Invalid table name in module manifest: ' . $key);
        }
        $tables[] = $table;
    }

    $depends = [];
    foreach ((array)($manifest['depends'] ?? []) as $dependency) {
        $dependency = (string)$dependency;
        if (!preg_match('/^[a-z][a-z0-9_]{1,39}$/', $dependency) || $dependency === $key) {
            throw new RuntimeException('Invalid dependency in module manifest: ' . $key);
        }
        $depends[] = $dependency;
    }

    return [
        'key' => $key,
        'label' => trim((string)($manifest['label'] ?? $key)) ?: $key,
        'label_en' => trim((string)($manifest['label_en'] ?? $key)) ?: $key,
        'description' => trim((string)($manifest['description'] ?? '')),
        'version' => trim((string)($manifest['version'] ?? '1.0.0')) ?: '1.0.0',
        'order' => (int)($manifest['order'] ?? 500),
        'default' => (bool)($manifest['default'] ?? false),
        'admin' => isset($manifest['admin']) && $manifest['admin'] !== '' ? (string)$manifest['admin'] : null,
        'api' => isset($manifest['api']) && $manifest['api'] !== '' ? (string)$manifest['api'] : null,
        'tables' => array_values(array_unique($tables)),
        'depends' => array_values(array_unique($depends)),
        'path' => cmsModuleRoot() . DIRECTORY_SEPARATOR . $key,
    ];
}

function cmsAvailableModules(bool $refresh = false): array
{
    static $modules = null;
    if (!$refresh && is_array($modules)) {
        return $modules;
    }

    $modules = [];
    $root = cmsModuleRoot();
    $dirs = glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
    sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($dirs as $dir) {
        $key = basename($dir);
        $manifestFile = $dir . DIRECTORY_SEPARATOR . 'module.php';
        if (!is_file($manifestFile)) {
            continue;
        }
        $manifest = require $manifestFile;
        if (!is_array($manifest)) {
            throw new RuntimeException('Module manifest must return an array: ' . $key);
        }
        $modules[$key] = cmsNormalizeModuleManifest($manifest, $key);
    }

    uasort($modules, static function (array $a, array $b): int {
        $order = $a['order'] <=> $b['order'];
        return $order !== 0 ? $order : strnatcasecmp($a['label_en'], $b['label_en']);
    });
    return $modules;
}

function cmsModuleManifest(string $key): array
{
    $modules = cmsAvailableModules();
    if (!isset($modules[$key])) {
        throw new InvalidArgumentException('Unknown module: ' . $key);
    }
    return $modules[$key];
}

function cmsModuleStates(PDO $pdo): array
{
    $states = [];
    foreach ($pdo->query('SELECT module_key, is_enabled FROM cms_modules')->fetchAll() as $row) {
        $states[(string)$row['module_key']] = (int)$row['is_enabled'] === 1;
    }
    return $states;
}

function cmsModuleEnabled(PDO $pdo, string $key): bool
{
    if (!isset(cmsAvailableModules()[$key])) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT is_enabled FROM cms_modules WHERE module_key=:k LIMIT 1');
    $stmt->execute([':k' => $key]);
    return (int)$stmt->fetchColumn() === 1;
}

function cmsRequireModule(string $key): void
{
    if (!isset(cmsAvailableModules()[$key])) {
        http_response_code(404);
        exit('Unknown module.');
    }
    if (!cmsModuleEnabled(cmsPdo(), $key)) {
        http_response_code(404);
        exit('Module is disabled.');
    }
}

function cmsResolveModuleDependencies(string $key, array &$resolved = [], array &$stack = []): array
{
    if (isset($resolved[$key])) {
        return array_keys($resolved);
    }
    if (isset($stack[$key])) {
        throw new RuntimeException('MODULE_DEPENDENCY_CYCLE: ' . $key);
    }
    $stack[$key] = true;
    $manifest = cmsModuleManifest($key);
    foreach ($manifest['depends'] as $dependency) {
        if (!isset(cmsAvailableModules()[$dependency])) {
            throw new RuntimeException('MODULE_DEPENDENCY_MISSING: ' . $key . ' -> ' . $dependency);
        }
        cmsResolveModuleDependencies($dependency, $resolved, $stack);
    }
    unset($stack[$key]);
    $resolved[$key] = true;
    return array_keys($resolved);
}

function cmsRunModuleMigrations(PDO $pdo, string $key): array
{
    $manifest = cmsModuleManifest($key);
    $executed = [];
    foreach (cmsResolveModuleDependencies($key) as $resolvedKey) {
        $resolvedManifest = cmsModuleManifest($resolvedKey);
        $ran = cmsRunMigrations(
            $pdo,
            'module:' . $resolvedKey,
            $resolvedManifest['path'] . DIRECTORY_SEPARATOR . 'migrations'
        );
        foreach ($ran as $migration) {
            $executed[] = $resolvedKey . '/' . $migration;
        }
    }
    return $executed;
}

function cmsModuleMigrationStatus(PDO $pdo, string $key): array
{
    $manifest = cmsModuleManifest($key);
    return cmsMigrationStatus(
        $pdo,
        'module:' . $key,
        $manifest['path'] . DIRECTORY_SEPARATOR . 'migrations'
    );
}

/**
 * Compatibility alias kept for v0.1/v0.2 integrations.
 * U3 installs modules through versioned migrations instead of schema.sql.
 */
function cmsInstallModuleSchema(PDO $pdo, string $key): void
{
    cmsRunModuleMigrations($pdo, $key);
}

function cmsModuleTables(): array
{
    $tables = [];
    foreach (cmsAvailableModules() as $manifest) {
        foreach ($manifest['tables'] as $table) {
            $tables[$table] = true;
        }
    }
    return array_keys($tables);
}

function cmsExpandModuleSelection(array $selected): array
{
    $expanded = [];
    foreach ($selected as $key) {
        if (!isset(cmsAvailableModules()[$key])) {
            continue;
        }
        foreach (cmsResolveModuleDependencies($key) as $resolvedKey) {
            $expanded[$resolvedKey] = true;
        }
    }
    return array_keys($expanded);
}

function cmsModuleDependents(string $key): array
{
    $out = [];
    foreach (cmsAvailableModules() as $moduleKey => $manifest) {
        if (in_array($key, $manifest['depends'], true)) {
            $out[] = $moduleKey;
        }
    }
    return $out;
}
