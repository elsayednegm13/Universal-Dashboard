<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$commands = [
    [PHP_BINARY, $root . '/tools/verify.php'],
    [PHP_BINARY, $root . '/tools/doctor.php', '--static'],
];

$failures = 0;
foreach ($commands as $command) {
    $display = implode(' ', array_map('escapeshellarg', $command));
    fwrite(STDOUT, "\n> {$display}\n");
    $proc = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root);
    if (!is_resource($proc)) {
        $failures++;
        continue;
    }
    $code = proc_close($proc);
    if ($code !== 0) {
        $failures++;
    }
}

$phpFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') continue;
    if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR)) continue;
    $phpFiles[] = $file->getPathname();
}
sort($phpFiles);
foreach ($phpFiles as $file) {
    $out = [];
    $code = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, "Syntax failure: {$file}\n" . implode("\n", $out) . "\n");
        $failures++;
    }
}
fwrite(STDOUT, "\nPHP syntax files=" . count($phpFiles) . "\n");
if ($failures) {
    fwrite(STDERR, "RELEASE CHECK FAILED failures={$failures}\n");
    exit(1);
}
fwrite(STDOUT, "RELEASE CHECK PASS\n");
