<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/module-registry.php';

$lock = __DIR__ . '/../storage/install.lock';
if (is_file($lock)) {
    http_response_code(403);
    exit('Installer is locked. Remove storage/install.lock only if you intentionally want to reinstall.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cmsVerifyCsrf();
    $siteName = trim((string)($_POST['site_name'] ?? ''));
    $baseUrl = rtrim(trim((string)($_POST['base_url'] ?? '')), '/');
    $allowedOrigins = trim((string)($_POST['allowed_origins'] ?? $baseUrl));
    $dbHost = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
    $dbPort = max(1, (int)($_POST['db_port'] ?? 3306));
    $dbName = trim((string)($_POST['db_name'] ?? ''));
    $dbUser = trim((string)($_POST['db_user'] ?? ''));
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $adminName = trim((string)($_POST['admin_name'] ?? ''));
    $adminEmail = mb_strtolower(trim((string)($_POST['admin_email'] ?? '')));
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $selected = cmsExpandModuleSelection(array_values(array_intersect(array_keys(cmsAvailableModules()), (array)($_POST['modules'] ?? []))));

    if ($siteName === '' || ($baseUrl !== '' && !filter_var($baseUrl, FILTER_VALIDATE_URL)) || array_filter(array_map('trim', explode(',', $allowedOrigins)), fn($o) => $o !== '' && !filter_var($o, FILTER_VALIDATE_URL)) || $dbName === '' || $dbUser === '' || $adminName === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 10) {
        $error = 'راجع البيانات المطلوبة. كلمة مرور المدير يجب ألا تقل عن 10 أحرف.';
    } else {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
            $schema = file_get_contents(__DIR__ . '/../database/core.sql');
            if ($schema === false) throw new RuntimeException('Cannot read core schema.');
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $schema) as $statement) {
                $statement = trim($statement);
                if ($statement !== '') $pdo->exec($statement);
            }
            cmsRunCoreMigrations($pdo);
            foreach ($selected as $moduleKey) {
                cmsInstallModuleSchema($pdo, $moduleKey);
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO admin_users (name,email,password_hash,is_active) VALUES (:name,:email,:hash,1) ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash),is_active=1');
            $stmt->execute([':name'=>$adminName, ':email'=>$adminEmail, ':hash'=>password_hash($adminPassword, PASSWORD_DEFAULT)]);
            $set = $pdo->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (:k,:v) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
            foreach (['site_name'=>$siteName,'base_url'=>$baseUrl,'default_locale'=>'ar','languages'=>'ar,en'] as $k=>$v) $set->execute([':k'=>$k,':v'=>$v]);
            $mod = $pdo->prepare('INSERT INTO cms_modules (module_key,is_enabled) VALUES (:k,:e) ON DUPLICATE KEY UPDATE is_enabled=VALUES(is_enabled)');
            foreach (cmsAvailableModules() as $k=>$meta) $mod->execute([':k'=>$k, ':e'=>in_array($k,$selected,true)?1:0]);
            $pdo->commit();

            $config = [
                'CMS_APP_NAME'=>$siteName,
                'CMS_BASE_URL'=>$baseUrl,
                'CMS_DEFAULT_LOCALE'=>'ar',
                'CMS_LANGUAGES'=>'ar,en',
                'CMS_THEME_DEFAULT'=>'system',
                'CMS_APP_KEY'=>bin2hex(random_bytes(32)),
                'CMS_ALLOWED_ORIGINS'=>$allowedOrigins,
                'CMS_DB_HOST'=>$dbHost,
                'CMS_DB_PORT'=>$dbPort,
                'CMS_DB_NAME'=>$dbName,
                'CMS_DB_USER'=>$dbUser,
                'CMS_DB_PASS'=>$dbPass,
            ];
            $configPhp = "<?php\nreturn " . var_export($config, true) . ";\n";
            if (file_put_contents(__DIR__ . '/../storage/config.local.php', $configPhp, LOCK_EX) === false) throw new RuntimeException('Cannot write local config.');
            @chmod(__DIR__ . '/../storage/config.local.php', 0600);
            file_put_contents($lock, date(DATE_ATOM), LOCK_EX);
            @chmod($lock, 0600);
            header('Location: /admin/login.php?installed=1');
            exit;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            error_log('[Universal CMS installer] ' . $e->getMessage());
            $error = 'فشل التثبيت. راجع بيانات قاعدة البيانات وصلاحية الكتابة على مجلد storage ثم أعد المحاولة.';
        }
    }
}
$modules = cmsAvailableModules();
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>تثبيت Universal CMS</title><script>(function(){try{if(matchMedia('(prefers-color-scheme:dark)').matches)document.documentElement.classList.add('admin-dark')}catch(e){}})();</script><link rel="stylesheet" href="/admin/assets/admin-theme.css"></head><body><main class="content" style="max-width:900px"><section class="card"><h1>تثبيت Universal CMS</h1><p class="muted">إعداد قاعدة البيانات والمدير والوحدات المطلوبة للمشروع الجديد.</p><?php if($error):?><div class="alert error"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif;?><form method="post"><?=cmsCsrfField()?><div class="form-grid"><label class="field"><span>اسم الموقع</span><input name="site_name" required></label><label class="field"><span>رابط الموقع</span><input name="base_url" placeholder="https://example.com"></label><label class="field"><span>Allowed Origins</span><input name="allowed_origins" placeholder="https://example.com,https://app.example.com"></label><label class="field"><span>DB Host</span><input name="db_host" value="127.0.0.1" required></label><label class="field"><span>DB Port</span><input name="db_port" type="number" value="3306" required></label><label class="field"><span>DB Name</span><input name="db_name" required></label><label class="field"><span>DB User</span><input name="db_user" required></label><label class="field"><span>DB Password</span><input name="db_pass" type="password"></label><div></div><label class="field"><span>اسم المدير</span><input name="admin_name" required></label><label class="field"><span>بريد المدير</span><input name="admin_email" type="email" required></label><label class="field"><span>كلمة مرور المدير</span><input name="admin_password" type="password" minlength="10" required></label></div><h2>الوحدات</h2><?php foreach($modules as $key=>$m):?><label class="module-row"><span><strong><?=htmlspecialchars($m['label'],ENT_QUOTES,'UTF-8')?></strong><small class="muted" style="display:block"><?=htmlspecialchars($m['description'],ENT_QUOTES,'UTF-8')?></small></span><input type="checkbox" name="modules[]" value="<?=htmlspecialchars($key,ENT_QUOTES,'UTF-8')?>" <?=$m['default']?'checked':''?>></label><?php endforeach;?><div style="margin-top:20px"><button class="btn primary" type="submit">تثبيت النظام</button></div></form></section></main></body></html>
