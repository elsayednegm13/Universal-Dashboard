<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);

function usage(): never
{
    fwrite(STDOUT, "Universal CMS Module Generator\n\nUsage:\n  php tools/generate-module.php <module_key> <label_ar> [label_en] [--dry-run]\n\nExample:\n  php tools/generate-module.php services \"الخدمات\" \"Services\"\n\n");
    exit(1);
}

$args = $argv;
array_shift($args);
$dryRun = false;
$args = array_values(array_filter($args, static function (string $arg) use (&$dryRun): bool {
    if ($arg === '--dry-run') {
        $dryRun = true;
        return false;
    }
    return true;
}));

if (count($args) < 2 || count($args) > 3) {
    usage();
}

[$key, $labelAr] = $args;
$labelEn = $args[2] ?? ucfirst(str_replace('_', ' ', $key));

if (!preg_match('/^[a-z][a-z0-9_]{1,29}$/', $key)) {
    fwrite(STDERR, "Invalid module key. Use 2-30 lowercase letters, digits, or underscores; start with a letter.\n");
    exit(2);
}
foreach ([$labelAr, $labelEn] as $label) {
    $labelLength = function_exists('mb_strlen') ? mb_strlen($label) : strlen($label);
    if (trim($label) === '' || $labelLength > 240 || preg_match('/[<>{}\\\'"]/', $label)) {
        fwrite(STDERR, "Labels must be non-empty and cannot contain quotes, braces, backslashes, or angle brackets.\n");
        exit(2);
    }
}

$table = 'cms_' . $key . '_items';
$moduleDir = $root . '/modules/' . $key;
$adminWrapper = $root . '/admin/' . $key . '.php';
$apiWrapper = $root . '/api/v1/' . $key . '.php';

foreach ([$moduleDir, $adminWrapper, $apiWrapper] as $target) {
    if (file_exists($target)) {
        fwrite(STDERR, "Target already exists: {$target}\nGenerator never overwrites existing modules.\n");
        exit(3);
    }
}

$templateRoot = __DIR__ . '/templates/module';
$map = [
    '{{MODULE_KEY}}' => $key,
    '{{LABEL_AR}}' => $labelAr,
    '{{LABEL_EN}}' => $labelEn,
    '{{TABLE_NAME}}' => $table,
];

$files = [
    $templateRoot . '/module.php.tpl' => $moduleDir . '/module.php',
    $templateRoot . '/admin.php.tpl' => $moduleDir . '/admin.php',
    $templateRoot . '/api.php.tpl' => $moduleDir . '/api.php',
    $templateRoot . '/README.md.tpl' => $moduleDir . '/README.md',
    $templateRoot . '/migrations/001_initial.sql.tpl' => $moduleDir . '/migrations/001_initial.sql',
    $templateRoot . '/admin-wrapper.php.tpl' => $adminWrapper,
    $templateRoot . '/api-wrapper.php.tpl' => $apiWrapper,
];

fwrite(STDOUT, "Module: {$key}\nTable: {$table}\n\n");
foreach ($files as $source => $destination) {
    $template = file_get_contents($source);
    if ($template === false) {
        fwrite(STDERR, "Cannot read template: {$source}\n");
        exit(4);
    }
    $content = strtr($template, $map);
    fwrite(STDOUT, ($dryRun ? '[dry-run] ' : '[create] ') . substr($destination, strlen($root) + 1) . "\n");
    if ($dryRun) {
        continue;
    }
    $dir = dirname($destination);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, "Cannot create directory: {$dir}\n");
        exit(5);
    }
    if (file_put_contents($destination, $content, LOCK_EX) === false) {
        fwrite(STDERR, "Cannot write file: {$destination}\n");
        exit(5);
    }
}

if (!$dryRun) {
    fwrite(STDOUT, "\nDone. Next steps:\n");
    fwrite(STDOUT, "  1. Review modules/{$key}/module.php and migrations/001_initial.sql\n");
    fwrite(STDOUT, "  2. Run: php tools/migrate.php --all\n");
    fwrite(STDOUT, "  3. Enable the module from /admin/modules.php\n");
    fwrite(STDOUT, "  4. Customize admin.php/api.php; make schema changes with NEW migrations only\n");
}
