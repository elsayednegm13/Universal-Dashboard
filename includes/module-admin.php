<?php

declare(strict_types=1);
require_once __DIR__.'/auth.php';
require_once __DIR__.'/csrf.php';
require_once __DIR__.'/audit.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/uploads.php';
require_once __DIR__.'/admin-ui.php';
require_once __DIR__.'/module-registry.php';

function cmsAdminModuleBoot(string $key): PDO
{
    cmsRequireAuth();$pdo=cmsPdo();cmsRequireModule($key);return $pdo;
}
function cmsAdminMessage(): string { return cmsStr($_GET['msg']??'',300); }
function cmsAdminRedirect(string $path,string $msg=''): never { header('Location: '.$path.($msg!==''?(str_contains($path,'?')?'&':'?').'msg='.rawurlencode($msg):''));exit; }
