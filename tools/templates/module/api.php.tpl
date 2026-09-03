<?php

declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';
cmsApiBootstrap();
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/module-registry.php';

$pdo = cmsPdo();
if (!cmsModuleEnabled($pdo, '{{MODULE_KEY}}')) {
    cmsJson(['ok'=>false,'error'=>'MODULE_DISABLED'], 404);
}

$rows = $pdo->query('SELECT id,slug,title_ar,title_en,summary_ar,summary_en,image_path,external_url,sort_order FROM {{TABLE_NAME}} WHERE is_active=1 ORDER BY sort_order,id')->fetchAll();
cmsJson(['ok'=>true,'data'=>$rows]);
