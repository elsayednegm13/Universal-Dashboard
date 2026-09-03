<?php

declare(strict_types=1);
require_once __DIR__ . '/../../includes/module-admin.php';
$pdo = cmsAdminModuleBoot('{{MODULE_KEY}}');
$base = '/admin/{{MODULE_KEY}}.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cmsVerifyCsrf();
    $action = cmsStr($_POST['action'] ?? 'save', 20);
    $id = cmsInt($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM {{TABLE_NAME}} WHERE id=:id');
        $stmt->execute([':id' => $id]);
        cmsAudit('{{MODULE_KEY}}.delete', '{{TABLE_NAME}}', (string)$id);
        cmsAdminRedirect($base, 'تم الحذف.');
    }
    if ($action === 'toggle' && $id > 0) {
        $stmt = $pdo->prepare('UPDATE {{TABLE_NAME}} SET is_active=1-is_active WHERE id=:id');
        $stmt->execute([':id' => $id]);
        cmsAudit('{{MODULE_KEY}}.toggle', '{{TABLE_NAME}}', (string)$id);
        cmsAdminRedirect($base, 'تم تحديث الحالة.');
    }

    $slug = cmsSlug(cmsStr($_POST['slug'] ?? '', 190));
    $titleAr = cmsStr($_POST['title_ar'] ?? '', 255);
    $titleEn = cmsStr($_POST['title_en'] ?? '', 255);
    $summaryAr = cmsStr($_POST['summary_ar'] ?? '');
    $summaryEn = cmsStr($_POST['summary_en'] ?? '');
    $imagePath = cmsStr($_POST['image_path'] ?? '', 500);
    $externalUrl = cmsStr($_POST['external_url'] ?? '', 500);
    $sortOrder = cmsInt($_POST['sort_order'] ?? 0);
    $active = cmsBool($_POST['is_active'] ?? 0);

    if ($slug === '') $slug = cmsSlug($titleEn !== '' ? $titleEn : $titleAr);
    if ($slug === '' || $titleAr === '' || $titleEn === '' || !cmsValidUrlOrBlank($externalUrl)) {
        cmsAdminRedirect($base, 'راجع العنوان والرابط.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE {{TABLE_NAME}} SET slug=:slug,title_ar=:ta,title_en=:te,summary_ar=:sa,summary_en=:se,image_path=:img,external_url=:url,sort_order=:sort,is_active=:active WHERE id=:id');
            $params = [':slug'=>$slug,':ta'=>$titleAr,':te'=>$titleEn,':sa'=>$summaryAr,':se'=>$summaryEn,':img'=>$imagePath ?: null,':url'=>$externalUrl ?: null,':sort'=>$sortOrder,':active'=>$active,':id'=>$id];
            $audit = '{{MODULE_KEY}}.update';
        } else {
            $stmt = $pdo->prepare('INSERT INTO {{TABLE_NAME}}(slug,title_ar,title_en,summary_ar,summary_en,image_path,external_url,sort_order,is_active) VALUES(:slug,:ta,:te,:sa,:se,:img,:url,:sort,:active)');
            $params = [':slug'=>$slug,':ta'=>$titleAr,':te'=>$titleEn,':sa'=>$summaryAr,':se'=>$summaryEn,':img'=>$imagePath ?: null,':url'=>$externalUrl ?: null,':sort'=>$sortOrder,':active'=>$active];
            $audit = '{{MODULE_KEY}}.create';
        }
        $stmt->execute($params);
        cmsAudit($audit, '{{TABLE_NAME}}', $id > 0 ? (string)$id : (string)$pdo->lastInsertId());
        cmsAdminRedirect($base, 'تم الحفظ.');
    } catch (PDOException $e) {
        error_log('[{{MODULE_KEY}} admin] ' . $e->getMessage());
        cmsAdminRedirect($base, 'تعذر الحفظ. تأكد من أن الـSlug غير مستخدم.');
    }
}

$edit = null;
if (($editId = cmsInt($_GET['edit'] ?? 0)) > 0) {
    $stmt = $pdo->prepare('SELECT * FROM {{TABLE_NAME}} WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $edit = $stmt->fetch() ?: null;
}
$rows = $pdo->query('SELECT * FROM {{TABLE_NAME}} ORDER BY sort_order,id')->fetchAll();

cmsAdminHead('{{LABEL_AR}}');
cmsAdminSidebar('{{MODULE_KEY}}');
cmsAdminTopbar('{{LABEL_AR}}');
?>
<div class="content">
<?php if ($m = cmsAdminMessage()): ?><div class="alert"><?=cmsE($m)?></div><?php endif; ?>
<div class="split">
<section class="card">
    <h2>{{LABEL_AR}}</h2>
    <div class="table-wrap"><table class="table"><thead><tr><th>العنوان</th><th>Slug</th><th>الحالة</th><th>الترتيب</th><th>إجراء</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?><tr>
        <td><?=cmsE((string)$row['title_ar'])?><div class="muted"><?=cmsE((string)$row['title_en'])?></div></td>
        <td><code><?=cmsE((string)$row['slug'])?></code></td>
        <td><span class="pill <?=$row['is_active'] ? 'ok' : 'bad'?>"><?=$row['is_active'] ? 'ظاهر' : 'مخفي'?></span></td>
        <td><?=intval($row['sort_order'])?></td>
        <td><div class="actions"><a class="btn" href="?edit=<?=$row['id']?>">تعديل</a><form method="post"><?=cmsCsrfField()?><input type="hidden" name="id" value="<?=$row['id']?>"><button class="btn" name="action" value="toggle">إظهار/إخفاء</button><button class="btn danger" name="action" value="delete" onclick="return confirm('تأكيد الحذف؟')">حذف</button></form></div></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<section class="card">
    <h2><?=$edit ? 'تعديل' : 'إضافة'?></h2>
    <form method="post"><?=cmsCsrfField()?><input type="hidden" name="id" value="<?=$edit['id'] ?? 0?>">
        <label class="field"><span>العنوان AR</span><input name="title_ar" required value="<?=cmsE((string)($edit['title_ar'] ?? ''))?>"></label>
        <label class="field"><span>Title EN</span><input name="title_en" required value="<?=cmsE((string)($edit['title_en'] ?? ''))?>"></label>
        <label class="field"><span>Slug</span><input name="slug" value="<?=cmsE((string)($edit['slug'] ?? ''))?>"></label>
        <label class="field"><span>الوصف AR</span><textarea name="summary_ar"><?=cmsE((string)($edit['summary_ar'] ?? ''))?></textarea></label>
        <label class="field"><span>Summary EN</span><textarea name="summary_en"><?=cmsE((string)($edit['summary_en'] ?? ''))?></textarea></label>
        <label class="field"><span>مسار الصورة</span><input name="image_path" value="<?=cmsE((string)($edit['image_path'] ?? ''))?>"></label>
        <label class="field"><span>رابط خارجي</span><input name="external_url" type="url" value="<?=cmsE((string)($edit['external_url'] ?? ''))?>"></label>
        <div class="form-grid"><label class="field"><span>الترتيب</span><input type="number" name="sort_order" value="<?=intval($edit['sort_order'] ?? 0)?>"></label><label class="module-row"><span>ظاهر</span><input class="check" type="checkbox" name="is_active" value="1" <?=cmsChecked($edit['is_active'] ?? 1)?>></label></div>
        <div class="actions" style="margin-top:16px"><button class="btn primary">حفظ</button><?php if ($edit): ?><a class="btn" href="<?=$base?>">إلغاء</a><?php endif; ?></div>
    </form>
</section>
</div></div>
<?php cmsAdminEnd();
