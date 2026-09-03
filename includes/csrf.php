<?php

declare(strict_types=1);
require_once __DIR__ . '/session.php';

function cmsCsrfToken(): string
{
    if (empty($_SESSION['cms_csrf'])) {
        $_SESSION['cms_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['cms_csrf'];
}

function cmsCsrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(cmsCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function cmsVerifyCsrf(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(cmsCsrfToken(), $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}
