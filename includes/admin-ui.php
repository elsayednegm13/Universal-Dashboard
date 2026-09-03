<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/module-registry.php';

function cmsE(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function cmsChecked(mixed $v): string { return in_array($v,[1,'1',true,'on','yes'],true)?'checked':''; }
function cmsSelected(mixed $a,mixed $b): string { return (string)$a===(string)$b?'selected':''; }
function cmsAdminAppName(): string {
    try{$s=cmsPdo()->prepare("SELECT setting_value FROM site_settings WHERE setting_key='site_name' LIMIT 1");$s->execute();$v=$s->fetchColumn();if(is_string($v)&&trim($v)!=='')return $v;}catch(Throwable $e){}
    return (string)cmsConfig()['app_name'];
}
function cmsAdminHead(string $title): void { $cfg=cmsConfig(); ?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=cmsE($title)?> | <?=cmsE(cmsAdminAppName())?></title><script>(function(){try{var p=localStorage.getItem('universal_cms_admin_theme')||'system';var d=p==='dark'||(p==='system'&&matchMedia('(prefers-color-scheme:dark)').matches);document.documentElement.classList.toggle('admin-dark',d);document.documentElement.dataset.themePreference=p}catch(e){}})();</script><link rel="stylesheet" href="/admin/assets/admin-theme.css"></head><body><?php }
function cmsAdminSidebar(string $active): void { $pdo=cmsPdo();$states=cmsModuleStates($pdo);$available=cmsAvailableModules(); ?>
<div class="shell"><aside id="cmsSidebar" class="sidebar"><div class="brand"><strong><?=cmsE(cmsAdminAppName())?></strong><span>Universal CMS</span></div><nav class="nav"><a class="<?=$active==='dashboard'?'active':''?>" href="/admin/">الرئيسية</a><a class="<?=$active==='settings'?'active':''?>" href="/admin/settings.php">إعدادات الموقع</a><a class="<?=$active==='account'?'active':''?>" href="/admin/account.php">الحساب</a><a class="<?=$active==='modules'?'active':''?>" href="/admin/modules.php">الوحدات</a><?php foreach($available as $key=>$module):if(!($states[$key]??false)||empty($module['admin']))continue;?><a class="<?=$active===$key?'active':''?>" href="<?=cmsE((string)$module['admin'])?>"><?=cmsE((string)$module['label'])?></a><?php endforeach;?></nav></aside><div id="cmsOverlay" class="overlay" onclick="cmsSetMenu(false)"></div><main class="main"><?php }
function cmsAdminTopbar(string $title): void { ?><header class="topbar"><div class="topbar-title"><button type="button" class="icon-btn mobile-menu" onclick="cmsSetMenu(true)" aria-label="فتح القائمة">☰</button><h1><?=cmsE($title)?></h1></div><div class="topbar-actions"><a class="btn optional" href="/" target="_blank" rel="noopener">الموقع</a><button id="cmsThemeBtn" type="button" class="icon-btn" onclick="cmsToggleTheme()" aria-label="تغيير الثيم">◐</button><a class="btn danger" href="/admin/logout.php">خروج</a></div></header><?php }
function cmsAdminEnd(): void { ?></main></div><script>
function cmsPref(){try{return localStorage.getItem('universal_cms_admin_theme')||'system'}catch(e){return'system'}}
function cmsApply(p,save){var dark=p==='dark'||(p==='system'&&matchMedia('(prefers-color-scheme:dark)').matches);document.documentElement.classList.toggle('admin-dark',dark);document.documentElement.dataset.themePreference=p;if(save){try{localStorage.setItem('universal_cms_admin_theme',p)}catch(e){}}}
function cmsToggleTheme(){var p=cmsPref(),n=p==='light'?'dark':(p==='dark'?'system':'light');cmsApply(n,true)}
function cmsSetMenu(open){document.getElementById('cmsSidebar')?.classList.toggle('open',open);document.getElementById('cmsOverlay')?.classList.toggle('open',open);document.body.style.overflow=open?'hidden':''}
document.addEventListener('keydown',e=>{if(e.key==='Escape')cmsSetMenu(false)});window.addEventListener('resize',()=>{if(innerWidth>950)cmsSetMenu(false)});if(window.matchMedia){matchMedia('(prefers-color-scheme:dark)').addEventListener?.('change',()=>{if(cmsPref()==='system')cmsApply('system',false)})}
</script></body></html><?php }
