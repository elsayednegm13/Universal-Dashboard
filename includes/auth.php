<?php

declare(strict_types=1);
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/config.php';

function cmsIsLoggedIn(): bool
{
    return !empty($_SESSION['cms_admin_id']);
}

function cmsRequireAuth(): void
{
    if (!cmsIsLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function cmsLoginFingerprint(string $email): string
{
    $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
    $secret=(string)(cmsConfig()['app_key']?:cmsConfig()['app_name']);
    return hash_hmac('sha256',mb_strtolower(trim($email)).'|'.$ip,$secret);
}

function cmsLoginIsThrottled(string $email): bool
{
    try{
        $s=cmsPdo()->prepare('SELECT attempt_count,window_started_at FROM admin_login_attempts WHERE fingerprint=:f LIMIT 1');
        $s->execute([':f'=>cmsLoginFingerprint($email)]);$r=$s->fetch();
        if(!$r)return false;
        $start=strtotime((string)$r['window_started_at'])?:0;
        return (time()-$start)<900 && (int)$r['attempt_count']>=5;
    }catch(Throwable $e){return false;}
}

function cmsRecordLoginFailure(string $email): void
{
    try{
        $pdo=cmsPdo();$fp=cmsLoginFingerprint($email);$s=$pdo->prepare('SELECT attempt_count,window_started_at FROM admin_login_attempts WHERE fingerprint=:f');$s->execute([':f'=>$fp]);$r=$s->fetch();$now=date('Y-m-d H:i:s');
        if(!$r||(time()-(strtotime((string)$r['window_started_at'])?:0))>=900){$count=1;$start=$now;}else{$count=(int)$r['attempt_count']+1;$start=(string)$r['window_started_at'];}
        $q=$pdo->prepare('INSERT INTO admin_login_attempts(fingerprint,attempt_count,window_started_at,last_attempt_at) VALUES(:f,:c,:w,:l) ON DUPLICATE KEY UPDATE attempt_count=VALUES(attempt_count),window_started_at=VALUES(window_started_at),last_attempt_at=VALUES(last_attempt_at)');$q->execute([':f'=>$fp,':c'=>$count,':w'=>$start,':l'=>$now]);
    }catch(Throwable $e){}
}

function cmsClearLoginFailures(string $email): void
{
    try{$s=cmsPdo()->prepare('DELETE FROM admin_login_attempts WHERE fingerprint=:f');$s->execute([':f'=>cmsLoginFingerprint($email)]);}catch(Throwable $e){}
}

function cmsAttemptLogin(string $email, string $password): bool
{
    $email=mb_strtolower(trim($email));
    if(cmsLoginIsThrottled($email)) return false;
    $stmt = cmsPdo()->prepare('SELECT id, name, email, password_hash, is_active FROM admin_users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user || (int)$user['is_active'] !== 1 || !password_verify($password, (string)$user['password_hash'])) {
        cmsRecordLoginFailure($email);
        return false;
    }
    cmsClearLoginFailures($email);
    session_regenerate_id(true);
    $_SESSION['cms_admin_id'] = (int)$user['id'];
    $_SESSION['cms_admin_name'] = (string)$user['name'];
    $_SESSION['cms_admin_email'] = (string)$user['email'];
    return true;
}
