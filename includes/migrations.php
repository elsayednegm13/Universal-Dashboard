<?php

declare(strict_types=1);

/**
 * Universal CMS migration runner.
 *
 * Migrations are append-only SQL files. Once applied, a checksum is stored.
 * Editing an already-applied migration is treated as drift and is rejected.
 */

function cmsEnsureMigrationTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS cms_schema_migrations (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      scope_key VARCHAR(120) NOT NULL,
      migration_key VARCHAR(190) NOT NULL,
      checksum CHAR(64) NOT NULL,
      applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_cms_migration_scope_key (scope_key,migration_key),
      INDEX idx_cms_migration_applied_at (applied_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}


function cmsMigrationTableExists(PDO $pdo): bool
{
    $stmt = $pdo->query("SHOW TABLES LIKE 'cms_schema_migrations'");
    return (bool)$stmt->fetchColumn();
}

function cmsMigrationFiles(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    $files = array_values(array_filter($files, 'is_file'));
    usort($files, static fn(string $a, string $b): int => strnatcasecmp(basename($a), basename($b)));
    return $files;
}


function cmsMigrationNormalizedContent(string $raw): string
{
    return str_replace(["\r\n", "\r"], "\n", $raw);
}

function cmsMigrationStatements(string $sql): array
{
    // The project migration contract intentionally supports plain SQL files
    // without DELIMITER blocks/stored procedures. This keeps deployment portable.
    $parts = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    return array_values(array_filter(array_map('trim', $parts), static fn(string $s): bool => $s !== ''));
}

function cmsAppliedMigrations(PDO $pdo, string $scope): array
{
    if (!cmsMigrationTableExists($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT migration_key,checksum,applied_at FROM cms_schema_migrations WHERE scope_key=:scope ORDER BY migration_key');
    $stmt->execute([':scope' => $scope]);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[(string)$row['migration_key']] = [
            'checksum' => (string)$row['checksum'],
            'applied_at' => (string)$row['applied_at'],
        ];
    }
    return $out;
}

function cmsMigrationStatus(PDO $pdo, string $scope, string $directory): array
{
    $applied = cmsAppliedMigrations($pdo, $scope);
    $pending = [];
    $drift = [];
    $known = [];

    foreach (cmsMigrationFiles($directory) as $path) {
        $key = basename($path);
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Cannot read migration: ' . $path);
        }
        $normalized = cmsMigrationNormalizedContent($raw);
        $checksum = hash('sha256', $normalized);
        $known[$key] = $checksum;
        if (!isset($applied[$key])) {
            $pending[] = $key;
            continue;
        }
        if (!hash_equals($applied[$key]['checksum'], $checksum)) {
            $drift[] = $key;
        }
    }

    return [
        'scope' => $scope,
        'applied' => array_keys($applied),
        'pending' => $pending,
        'drift' => $drift,
        'known' => array_keys($known),
    ];
}

function cmsRunMigrations(PDO $pdo, string $scope, string $directory): array
{
    cmsEnsureMigrationTable($pdo);
    $applied = cmsAppliedMigrations($pdo, $scope);
    $executed = [];

    foreach (cmsMigrationFiles($directory) as $path) {
        $key = basename($path);
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Cannot read migration: ' . $path);
        }
        $normalized = cmsMigrationNormalizedContent($raw);
        $checksum = hash('sha256', $normalized);

        if (isset($applied[$key])) {
            if (!hash_equals($applied[$key]['checksum'], $checksum)) {
                throw new RuntimeException('MIGRATION_CHECKSUM_MISMATCH: ' . $scope . '/' . $key);
            }
            continue;
        }

        foreach (cmsMigrationStatements($normalized) as $statement) {
            $pdo->exec($statement);
        }

        $stmt = $pdo->prepare('INSERT INTO cms_schema_migrations(scope_key,migration_key,checksum) VALUES(:scope,:key,:checksum)');
        $stmt->execute([':scope' => $scope, ':key' => $key, ':checksum' => $checksum]);
        $executed[] = $key;
        $applied[$key] = ['checksum' => $checksum, 'applied_at' => date('Y-m-d H:i:s')];
    }

    return $executed;
}

function cmsRunCoreMigrations(PDO $pdo): array
{
    return cmsRunMigrations($pdo, 'core', __DIR__ . '/../database/migrations');
}
