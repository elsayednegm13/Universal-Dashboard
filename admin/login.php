<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/admin-ui.php';
if (cmsIsLoggedIn()) { header('Location: /admin/'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') { cmsVerifyCsrf(); if(cmsAttemptLogin((string)($_POST['email']??''),(string)($_POST['password']??''))){header('Location: /admin/');exit;} $error='بيانات الدخول غير صحيحة.'; }
cmsAdminHead('تسجيل الدخول');
?><main class="content" style="max-width:480px;padding-top:10vh"><section class="card"><h1>تسجيل الدخول</h1><p class="muted"><?=cmsE(cmsConfig()['app_name'])?></p><?php if($error):?><div class="alert error"><?=cmsE($error)?></div><?php endif;?><form method="post"><?=cmsCsrfField()?><label class="field"><span>البريد الإلكتروني</span><input type="email" name="email" required autocomplete="username"></label><label class="field" style="margin-top:14px"><span>كلمة المرور</span><input type="password" name="password" required autocomplete="current-password"></label><button class="btn primary" style="width:100%;margin-top:18px" type="submit">دخول</button></form></section></main><script>function toggleTheme(){}</script></body></html>
