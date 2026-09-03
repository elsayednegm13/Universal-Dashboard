<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(2);
require_once __DIR__ . '/../../includes/config.php';

$cfg = cmsConfig()['db'];
$name = (string)$cfg['name'];
if ($name === '' || !preg_match('/(?:test|ci)/i', $name)) {
    fwrite(STDERR, "Refusing destructive reset: database name must contain 'test' or 'ci'.\n");
    exit(3);
}
if (!extension_loaded('pdo_mysql')) {
    fwrite(STDERR, "pdo_mysql is required.\n");
    exit(4);
}
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $name);
$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $table) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$table)) continue;
    $pdo->exec('DROP TABLE `' . $table . '`');
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
fwrite(STDOUT, 'RESET PASS tables=' . count($tables) . PHP_EOL);
