<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
cmsRequireAuth();
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/admin-ui.php';
require_once __DIR__ . '/../includes/module-registry.php';

$pdo = cmsPdo();
$available = cmsAvailableModules();
$message = '';
$messageType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cmsVerifyCsrf();
    $action = cmsStr($_POST['action'] ?? 'save', 40);
    try {
        if ($action === 'run_migrations') {
            $executed = cmsRunCoreMigrations($pdo);
            $states = cmsModuleStates($pdo);
            foreach ($available as $key => $manifest) {
                if (!($states[$key] ?? false)) {
                    continue;
                }
                foreach (cmsRunModuleMigrations($pdo, $key) as $migration) {
                    $executed[] = $migration;
                }
            }
            cmsAudit('modules.migrate', 'cms_modules', json_encode(['executed'=>count($executed)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            $message = $executed ? 'تم تطبيق ' . count($executed) . ' تحديث قاعدة بيانات.' : 'قاعدة البيانات محدثة بالفعل.';
        } else {
            $requested = array_values(array_intersect(array_keys($available), (array)($_POST['enabled'] ?? [])));
            $enabled = cmsExpandModuleSelection($requested);

            foreach ($enabled as $key) {
                cmsRunModuleMigrations($pdo, $key);
            }

            $stmt = $pdo->prepare('INSERT INTO cms_modules(module_key,is_enabled) VALUES(:k,:e) ON DUPLICATE KEY UPDATE is_enabled=VALUES(is_enabled)');
            foreach ($available as $key => $manifest) {
                $stmt->execute([':k' => $key, ':e' => in_array($key, $enabled, true) ? 1 : 0]);
            }
            cmsAudit('modules.update', 'cms_modules');
            $message = 'تم تحديث الوحدات.';
        }
    } catch (Throwable $e) {
        error_log('[CMS modules] ' . $e->getMessage());
        $messageType = 'error';
        $message = str_contains($e->getMessage(), 'MIGRATION_CHECKSUM_MISMATCH')
            ? 'تم اكتشاف Migration معدلة بعد تطبيقها. أعد الملف الأصلي أو أنشئ Migration جديدة.'
            : 'تعذر تحديث الوحدات أو قاعدة البيانات. راجع السجل والصلاحيات.';
    }
}

$states = cmsModuleStates($pdo);
$coreStatus = cmsMigrationStatus($pdo, 'core', __DIR__ . '/../database/migrations');
$statuses = [];
foreach ($available as $key => $manifest) {
    $statuses[$key] = cmsModuleMigrationStatus($pdo, $key);
}

cmsAdminHead('الوحدات');
cmsAdminSidebar('modules');
cmsAdminTopbar('الوحدات');
?>
<div class="content">
<?php if ($message): ?><div class="alert <?=$messageType === 'error' ? 'error' : ''?>"><?=cmsE($message)?></div><?php endif; ?>
<section class="card">
    <div class="actions" style="justify-content:space-between;margin-bottom:14px">
        <div>
            <h2 style="margin:0">الوحدات</h2>
            <?php $pendingCount = count($coreStatus['pending']); foreach ($statuses as $st) $pendingCount += count($st['pending']); ?>
            <?php if ($pendingCount > 0): ?><span class="pill bad">تحديثات معلقة: <?=$pendingCount?></span><?php else: ?><span class="pill ok">قاعدة البيانات محدثة</span><?php endif; ?>
        </div>
        <form method="post"><?=cmsCsrfField()?><button class="btn" name="action" value="run_migrations">تشغيل التحديثات</button></form>
    </div>
    <form method="post">
        <?=cmsCsrfField()?>
        <input type="hidden" name="action" value="save">
        <?php foreach ($available as $key => $module): $st = $statuses[$key]; $hasDrift = !empty($st['drift']); ?>
            <label class="module-row">
                <span>
                    <strong><?=cmsE($module['label'])?></strong>
                    <span class="muted" style="display:block;font-size:13px;margin-top:4px"><?=cmsE($module['description'])?></span>
                    <span class="muted" style="display:block;font-size:12px;margin-top:5px">v<?=cmsE($module['version'])?><?php if ($st['pending']): ?> · تحديثات <?=count($st['pending'])?><?php endif; ?><?php if ($hasDrift): ?> · <strong>Migration Drift</strong><?php endif; ?></span>
                </span>
                <input class="check" type="checkbox" name="enabled[]" value="<?=cmsE($key)?>" <?=($states[$key] ?? false) ? 'checked' : ''?>>
            </label>
        <?php endforeach; ?>
        <button class="btn primary" style="margin-top:18px" type="submit">حفظ الوحدات</button>
    </form>
</section>
</div>
<?php cmsAdminEnd();
