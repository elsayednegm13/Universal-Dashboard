<?php

declare(strict_types=1);
require_once __DIR__ . '/../../includes/module-admin.php';
require_once __DIR__ . '/../../includes/backup.php';
$pdo = cmsAdminModuleBoot('backups');
$base = '/admin/backups.php';
$dir = cmsBackupDir();

if (isset($_GET['download'])) {
    $name = basename((string)$_GET['download']);
    $path = $dir . '/' . $name;
    if (!preg_match('/^cms-[A-Za-z0-9-]+\.cmsbak$/', $name) || !is_file($path)) { http_response_code(404); exit; }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cmsVerifyCsrf();
    $action = cmsStr($_POST['action'] ?? 'create', 20);
    try {
        if ($action === 'create') {
            $name = cmsCreateBackup($pdo);
            cmsAudit('backup.create', 'backup', $name);
            cmsAdminRedirect($base, 'تم إنشاء النسخة الاحتياطية.');
        }
        $name = basename(cmsStr($_POST['name'] ?? '', 180));
        $path = $dir . '/' . $name;
        if (!preg_match('/^cms-[A-Za-z0-9-]+\.cmsbak$/', $name) || !is_file($path)) cmsAdminRedirect($base, 'ملف غير صحيح.');
        if ($action === 'delete') {
            if (!unlink($path)) throw new RuntimeException('BACKUP_DELETE_FAILED');
            cmsAudit('backup.delete', 'backup', $name);
            cmsAdminRedirect($base, 'تم حذف النسخة.');
        }
        if ($action === 'restore') {
            if ((string)($_POST['confirm'] ?? '') !== 'RESTORE') cmsAdminRedirect($base, 'اكتب RESTORE للتأكيد.');
            $payload = cmsReadBackup($path);
            cmsValidateRestoreCompatibility($pdo, $payload);
            cmsRestoreBackup($pdo, $path);
            cmsAudit('backup.restore', 'backup', $name);
            cmsAdminRedirect($base, 'تم الاسترجاع وإنشاء نسخة تلقائية قبل العملية.');
        }
    } catch (Throwable $e) {
        error_log('[CMS backup] ' . $e->getMessage());
        $msg = str_starts_with($e->getMessage(), 'RESTORE_') || str_starts_with($e->getMessage(), 'BACKUP_')
            ? 'تم إيقاف العملية لأن النسخة غير متوافقة أو غير سليمة. راجع سجل السيرفر قبل المحاولة مرة أخرى.'
            : 'تعذر تنفيذ العملية. راجع مساحة التخزين والصلاحيات.';
        cmsAdminRedirect($base, $msg);
    }
}

$files = [];
foreach (glob($dir . '/cms-*.cmsbak') ?: [] as $path) {
    $files[] = ['name'=>basename($path),'size'=>filesize($path),'time'=>filemtime($path),'summary'=>cmsBackupSummary($path)];
}
usort($files, static fn(array $a, array $b): int => $b['time'] <=> $a['time']);

cmsAdminHead('النسخ الاحتياطية');
cmsAdminSidebar('backups');
cmsAdminTopbar('النسخ الاحتياطية');
?>
<div class="content">
<?php if ($m = cmsAdminMessage()): ?><div class="alert"><?=cmsE($m)?></div><?php endif; ?>
<section class="card">
    <div class="actions" style="justify-content:space-between">
        <div><h2 style="margin:0 0 5px">Backup / Restore</h2><span class="muted">الاسترجاع يفحص سلامة الملف وتوافق الـSchema قبل أي تغيير، وينشئ Pre-Restore Backup تلقائيًا.</span></div>
        <form method="post"><?=cmsCsrfField()?><button class="btn primary" name="action" value="create">إنشاء نسخة الآن</button></form>
    </div>
</section>
<section class="card" style="margin-top:18px">
<div class="table-wrap"><table class="table"><thead><tr><th>الملف</th><th>الإصدار</th><th>المحتوى</th><th>الحجم</th><th>التاريخ</th><th>إجراء</th></tr></thead><tbody>
<?php foreach ($files as $f): $s=$f['summary']; ?>
<tr>
<td><code><?=cmsE($f['name'])?></code><?php if(!$s['valid']):?><div><span class="pill bad">ملف غير صالح</span></div><?php endif;?></td>
<td><?= $s['valid'] ? 'CMS v'.cmsE((string)$s['platform_version']).' / B'.$s['backup_version'] : '—' ?></td>
<td><?= $s['valid'] ? (int)$s['tables'].' جدول · '.(int)$s['uploads'].' ملف' : '—' ?></td>
<td><?=number_format($f['size']/1024,1)?> KB</td>
<td><?=date('Y-m-d H:i',$f['time'])?></td>
<td><div class="actions"><a class="btn" href="?download=<?=rawurlencode($f['name'])?>">تحميل</a><?php if($s['valid']):?><form method="post"><?=cmsCsrfField()?><input type="hidden" name="name" value="<?=cmsE($f['name'])?>"><input name="confirm" placeholder="RESTORE" autocomplete="off"><button class="btn" name="action" value="restore" onclick="return confirm('سيتم فحص النسخة ثم استرجاع البيانات. هل أنت متأكد؟')">استرجاع</button><button class="btn danger" name="action" value="delete" onclick="return confirm('حذف النسخة؟')">حذف</button></form><?php endif;?></div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php if(!$files):?><div class="empty">لا توجد نسخ احتياطية بعد.</div><?php endif;?>
</section></div>
<?php cmsAdminEnd();
