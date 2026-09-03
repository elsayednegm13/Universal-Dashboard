<?php

declare(strict_types=1);
require_once __DIR__ . '/database.php';

function cmsSettings(?PDO $pdo = null): array
{
    $pdo ??= cmsPdo();
    $out=[];
    foreach($pdo->query('SELECT setting_key,setting_value FROM site_settings')->fetchAll() as $row) $out[(string)$row['setting_key']] = $row['setting_value'];
    return $out;
}

function cmsSetting(string $key, mixed $default=null, ?PDO $pdo=null): mixed
{
    $pdo ??= cmsPdo();
    $stmt=$pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key=:k LIMIT 1');$stmt->execute([':k'=>$key]);$v=$stmt->fetchColumn();
    return $v===false?$default:$v;
}

function cmsSetSetting(string $key, ?string $value, ?PDO $pdo=null): void
{
    $pdo ??= cmsPdo();
    $stmt=$pdo->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(:k,:v) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $stmt->execute([':k'=>$key,':v'=>$value]);
}

function cmsJson(mixed $data, int $status=200): never
{
    http_response_code($status);header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}

function cmsSlug(string $value): string
{
    $value=trim(mb_strtolower($value));
    $value=preg_replace('/[^\p{L}\p{N}]+/u','-',$value)??'';
    return trim($value,'-');
}

function cmsBool(mixed $value): int { return in_array($value,[1,'1',true,'on','yes'],true)?1:0; }
function cmsInt(mixed $value,int $default=0): int { return is_numeric($value)?(int)$value:$default; }
function cmsStr(mixed $value,int $max=65535): string { return mb_substr(trim((string)$value),0,$max); }
function cmsValidUrlOrBlank(string $value): bool { return $value==='' || (bool)filter_var($value,FILTER_VALIDATE_URL); }

function cmsPublicBaseUrl(): string
{
    $db=(string)cmsSetting('base_url',''); if($db!=='') return rtrim($db,'/'); return cmsConfig()['base_url'];
}

function cmsRequestJson(): array
{
    $raw=file_get_contents('php://input');
    if($raw===false||trim($raw)==='') return [];
    $data=json_decode($raw,true);return is_array($data)?$data:[];
}

function cmsSafeFilename(string $name): string
{
    $name=basename($name);$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$base=pathinfo($name,PATHINFO_FILENAME);
    $base=preg_replace('/[^A-Za-z0-9_-]+/','-', $base) ?: 'file';
    return trim($base,'-_') . ($ext!==''?'.'.$ext:'');
}
