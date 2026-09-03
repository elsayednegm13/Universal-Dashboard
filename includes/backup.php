<?php

declare(strict_types=1);
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/module-registry.php';
require_once __DIR__ . '/version.php';

function cmsBackupTables(): array
{
    $core = ['admin_users','admin_login_attempts','site_settings','cms_modules','cms_schema_migrations','cms_release_history','audit_logs'];
    return array_values(array_unique(array_merge($core, cmsModuleTables())));
}

function cmsTableExists(PDO $pdo, string $table): bool
{
    $s = $pdo->prepare('SHOW TABLES LIKE :t');
    $s->execute([':t' => $table]);
    return (bool)$s->fetchColumn();
}

function cmsTableDescription(PDO $pdo, string $table): array
{
    if (!in_array($table, cmsBackupTables(), true) || !cmsTableExists($pdo, $table)) return [];
    $out = [];
    foreach ($pdo->query('DESCRIBE `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[(string)$row['Field']] = [
            'type' => (string)$row['Type'],
            'null' => (string)$row['Null'],
            'default' => $row['Default'],
            'extra' => (string)$row['Extra'],
        ];
    }
    return $out;
}

function cmsTableColumns(PDO $pdo, string $table): array
{
    return array_keys(cmsTableDescription($pdo, $table));
}

function cmsBackupDir(): string
{
    $d = __DIR__ . '/../storage/backups';
    if (!is_dir($d) && !mkdir($d, 0770, true) && !is_dir($d)) throw new RuntimeException('BACKUP_DIR_CREATE_FAILED');
    return $d;
}

function cmsCollectUploadFiles(int $maxTotal = 104857600): array
{
    $root = realpath(__DIR__ . '/../uploads');
    if (!$root) return [];
    $out = [];
    $total = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->isLink()) continue;
        $size = $file->getSize();
        if ($size > 20 * 1024 * 1024) continue;
        $total += $size;
        if ($total > $maxTotal) throw new RuntimeException('UPLOAD_BACKUP_TOO_LARGE');
        $real = $file->getRealPath();
        if (!$real || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) continue;
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen($root) + 1));
        $bin = file_get_contents($real);
        if ($bin === false) continue;
        $out[] = ['path'=>$rel,'sha256'=>hash('sha256',$bin),'size'=>strlen($bin),'data'=>base64_encode($bin)];
    }
    return $out;
}

function cmsBackupMigrationSnapshot(PDO $pdo): array
{
    if (!cmsMigrationTableExists($pdo)) return [];
    $rows = $pdo->query('SELECT scope_key,migration_key,checksum,applied_at FROM cms_schema_migrations ORDER BY scope_key,migration_key')->fetchAll(PDO::FETCH_ASSOC);
    return array_map(static fn(array $r): array => [
        'scope' => (string)$r['scope_key'],
        'key' => (string)$r['migration_key'],
        'checksum' => (string)$r['checksum'],
        'applied_at' => (string)$r['applied_at'],
    ], $rows);
}

function cmsCreateBackup(PDO $pdo, string $reason = 'manual'): string
{
    $tables = [];
    $schema = [];
    foreach (cmsBackupTables() as $table) {
        if (!cmsTableExists($pdo, $table)) continue;
        $tables[$table] = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
        $schema[$table] = cmsTableDescription($pdo, $table);
    }
    $payload = [
        'format' => 'universal-cms-backup',
        'version' => 3,
        'platform_version' => UNIVERSAL_CMS_VERSION,
        'created_at' => date(DATE_ATOM),
        'reason' => $reason,
        'schema' => $schema,
        'migrations' => cmsBackupMigrationSnapshot($pdo),
        'tables' => $tables,
        'uploads' => cmsCollectUploadFiles(),
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) throw new RuntimeException('BACKUP_JSON_FAILED');
    $envelope = ['format'=>'universal-cms-envelope','sha256'=>hash('sha256',$json),'payload'=>base64_encode($json)];
    $raw = json_encode($envelope, JSON_UNESCAPED_SLASHES);
    if ($raw === false) throw new RuntimeException('BACKUP_ENVELOPE_FAILED');
    $compressed = gzencode($raw, 6);
    if ($compressed === false) throw new RuntimeException('BACKUP_COMPRESS_FAILED');
    $name = 'cms-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.cmsbak';
    if (file_put_contents(cmsBackupDir() . '/' . $name, $compressed, LOCK_EX) === false) throw new RuntimeException('BACKUP_WRITE_FAILED');
    return $name;
}

function cmsReadBackup(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) throw new RuntimeException('BACKUP_READ_FAILED');
    $decoded = gzdecode($raw);
    if ($decoded === false) throw new RuntimeException('BACKUP_DECOMPRESS_FAILED');
    $env = json_decode($decoded, true);
    if (!is_array($env) || ($env['format'] ?? '') !== 'universal-cms-envelope' || empty($env['payload']) || empty($env['sha256'])) throw new RuntimeException('BACKUP_INVALID');
    $json = base64_decode((string)$env['payload'], true);
    if ($json === false || !hash_equals((string)$env['sha256'], hash('sha256', $json))) throw new RuntimeException('BACKUP_HASH_MISMATCH');
    $p = json_decode($json, true);
    if (!is_array($p) || ($p['format'] ?? '') !== 'universal-cms-backup' || !in_array((int)($p['version'] ?? 0), [2,3], true)) throw new RuntimeException('BACKUP_INCOMPATIBLE');
    return $p;
}

function cmsValidateBackupUploads(array $payload): void
{
    foreach ((array)($payload['uploads'] ?? []) as $file) {
        if (!is_array($file) || empty($file['path']) || !array_key_exists('data', $file)) throw new RuntimeException('BACKUP_UPLOAD_INVALID');
        $rel = str_replace('\\', '/', (string)$file['path']);
        if ($rel === '' || str_contains($rel, '..') || str_starts_with($rel, '/') || str_contains($rel, "\0")) throw new RuntimeException('BACKUP_UPLOAD_PATH_INVALID');
        $bin = base64_decode((string)$file['data'], true);
        if ($bin === false || !hash_equals((string)($file['sha256'] ?? ''), hash('sha256', $bin))) throw new RuntimeException('BACKUP_UPLOAD_HASH_MISMATCH');
        if (isset($file['size']) && (int)$file['size'] !== strlen($bin)) throw new RuntimeException('BACKUP_UPLOAD_SIZE_MISMATCH');
    }
}

function cmsValidateRestoreCompatibility(PDO $pdo, array $payload): void
{
    cmsValidateBackupUploads($payload);
    if ((int)($payload['version'] ?? 0) < 3) return; // v2 compatibility path from v0.3.

    foreach ((array)($payload['schema'] ?? []) as $table => $backupColumns) {
        if (!is_string($table) || !in_array($table, cmsBackupTables(), true)) continue;
        if (!cmsTableExists($pdo, $table)) throw new RuntimeException('RESTORE_TABLE_MISSING: ' . $table);
        $current = cmsTableDescription($pdo, $table);
        $backupColumns = is_array($backupColumns) ? $backupColumns : [];
        foreach (array_keys($backupColumns) as $column) {
            if (!array_key_exists((string)$column, $current)) throw new RuntimeException('RESTORE_COLUMN_MISSING: ' . $table . '.' . $column);
        }
        foreach ($current as $column => $meta) {
            if (array_key_exists($column, $backupColumns)) continue;
            $extra = strtolower((string)($meta['extra'] ?? ''));
            $nullable = strtoupper((string)($meta['null'] ?? 'NO')) === 'YES';
            $hasDefault = array_key_exists('default', $meta) && $meta['default'] !== null;
            if (!$nullable && !$hasDefault && !str_contains($extra, 'auto_increment') && !str_contains($extra, 'generated')) {
                throw new RuntimeException('RESTORE_REQUIRED_COLUMN_NOT_IN_BACKUP: ' . $table . '.' . $column);
            }
        }
    }

    $core = cmsMigrationStatus($pdo, 'core', __DIR__ . '/../database/migrations');
    if ($core['drift']) throw new RuntimeException('RESTORE_BLOCKED_MIGRATION_DRIFT: core');
    foreach (cmsAvailableModules() as $key => $manifest) {
        $status = cmsModuleMigrationStatus($pdo, $key);
        if ($status['drift']) throw new RuntimeException('RESTORE_BLOCKED_MIGRATION_DRIFT: ' . $key);
    }
}

function cmsRestoreBackup(PDO $pdo, string $path): void
{
    $payload = cmsReadBackup($path);
    cmsValidateRestoreCompatibility($pdo, $payload);
    cmsCreateBackup($pdo, 'pre-restore');

    $pdo->beginTransaction();
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ((array)($payload['tables'] ?? []) as $table => $rows) {
            if (!is_string($table) || !in_array($table, cmsBackupTables(), true) || !cmsTableExists($pdo, $table)) continue;
            $pdo->exec('DELETE FROM `' . $table . '`');
            $allowed = cmsTableColumns($pdo, $table);
            foreach ((array)$rows as $row) {
                if (!is_array($row) || !$row) continue;
                $row = array_intersect_key($row, array_flip($allowed));
                if (!$row) continue;
                $cols = array_keys($row);
                $names = implode(',', array_map(static fn(string $c): string => '`' . $c . '`', $cols));
                $params = implode(',', array_fill(0, count($cols), '?'));
                $stmt = $pdo->prepare('INSERT INTO `' . $table . '` (' . $names . ') VALUES (' . $params . ')');
                $stmt->execute(array_values($row));
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $pdo->commit();
    } catch (Throwable $e) {
        try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $ignored) {}
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    $root = realpath(__DIR__ . '/../uploads');
    if (!$root) throw new RuntimeException('UPLOAD_ROOT_MISSING');
    foreach ((array)($payload['uploads'] ?? []) as $file) {
        $rel = str_replace('\\', '/', (string)$file['path']);
        $bin = base64_decode((string)$file['data'], true);
        if ($bin === false) throw new RuntimeException('BACKUP_UPLOAD_DECODE_FAILED');
        $dest = $root . '/' . $rel;
        $dir = dirname($dest);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('UPLOAD_RESTORE_DIR_FAILED');
        if (file_put_contents($dest, $bin, LOCK_EX) === false) throw new RuntimeException('UPLOAD_RESTORE_WRITE_FAILED');
    }
}

function cmsBackupSummary(string $path): array
{
    try {
        $payload = cmsReadBackup($path);
        return [
            'valid' => true,
            'backup_version' => (int)($payload['version'] ?? 0),
            'platform_version' => (string)($payload['platform_version'] ?? '0.3.x'),
            'created_at' => (string)($payload['created_at'] ?? ''),
            'reason' => (string)($payload['reason'] ?? ''),
            'tables' => count((array)($payload['tables'] ?? [])),
            'uploads' => count((array)($payload['uploads'] ?? [])),
        ];
    } catch (Throwable $e) {
        return ['valid'=>false,'error'=>$e->getMessage(),'backup_version'=>0,'platform_version'=>'','created_at'=>'','reason'=>'','tables'=>0,'uploads'=>0];
    }
}
