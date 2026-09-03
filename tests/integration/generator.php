<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(2);
$source = dirname(__DIR__, 2);
$tmp = sys_get_temp_dir() . '/universal-cms-generator-' . bin2hex(random_bytes(5));

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    rmdir($dir);
}
function copytree(string $src, string $dst): void {
    mkdir($dst, 0700, true);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $item) {
        $target = $dst . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($src) + 1);
        if ($item->isDir()) { if (!is_dir($target)) mkdir($target, 0700, true); }
        else copy($item->getPathname(), $target);
    }
}

try {
    copytree($source, $tmp);
    @unlink($tmp . '/storage/config.local.php');
    @unlink($tmp . '/storage/install.lock');
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp . '/tools/generate-module.php') . ' services ' . escapeshellarg('الخدمات') . ' Services 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) throw new RuntimeException("generator failed\n" . implode("\n", $out));
    foreach (['modules/services/module.php','modules/services/admin.php','modules/services/api.php','modules/services/migrations/001_initial.sql','admin/services.php','api/v1/services.php'] as $path) {
        if (!is_file($tmp . '/' . $path)) throw new RuntimeException('missing generated file: ' . $path);
    }
    $verify = [];
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp . '/tools/verify.php') . ' 2>&1', $verify, $verifyCode);
    if ($verifyCode !== 0) throw new RuntimeException("verify after generator failed\n" . implode("\n", $verify));
    require $tmp . '/includes/module-registry.php';
    $modules = cmsAvailableModules(true);
    if (!isset($modules['services'])) throw new RuntimeException('generated module not discovered');
    echo 'GENERATOR PASS modules=' . count($modules) . PHP_EOL;
} finally {
    rrmdir($tmp);
}
