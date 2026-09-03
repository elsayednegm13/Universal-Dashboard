<?php

declare(strict_types=1);

function cmsLocalConfig(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    $config = [];
    $path = __DIR__ . '/../storage/config.local.php';
    if (is_file($path)) {
        $loaded = require $path;
        if (is_array($loaded)) {
            $config = $loaded;
        }
    }
    return $config;
}

function cmsEnv(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    $config = cmsLocalConfig();
    return $config[$key] ?? $default;
}

function cmsConfig(): array
{
    return [
        'app_name' => (string)cmsEnv('CMS_APP_NAME', 'Universal CMS'),
        'base_url' => rtrim((string)cmsEnv('CMS_BASE_URL', ''), '/'),
        'default_locale' => (string)cmsEnv('CMS_DEFAULT_LOCALE', 'ar'),
        'languages' => array_values(array_filter(array_map('trim', explode(',', (string)cmsEnv('CMS_LANGUAGES', 'ar,en'))))),
        'theme_default' => (string)cmsEnv('CMS_THEME_DEFAULT', 'system'),
        'app_key' => (string)cmsEnv('CMS_APP_KEY', ''),
        'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string)cmsEnv('CMS_ALLOWED_ORIGINS', ''))))),
        'db' => [
            'host' => (string)cmsEnv('CMS_DB_HOST', '127.0.0.1'),
            'port' => (int)cmsEnv('CMS_DB_PORT', 3306),
            'name' => (string)cmsEnv('CMS_DB_NAME', ''),
            'user' => (string)cmsEnv('CMS_DB_USER', ''),
            'pass' => (string)cmsEnv('CMS_DB_PASS', ''),
            'charset' => 'utf8mb4',
        ],
    ];
}
