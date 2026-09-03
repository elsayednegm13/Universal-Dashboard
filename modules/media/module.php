<?php

declare(strict_types=1);

return [
    'key' => 'media',
    'label' => 'مكتبة الوسائط',
    'label_en' => 'Media Library',
    'description' => 'رفع وإدارة الصور والملفات المحلية',
    'version' => '1.0.0',
    'order' => 110,
    'default' => true,
    'admin' => '/admin/media.php',
    'api' => '/api/v1/media.php',
    'tables' => ['media_items'],
    'depends' => [],
];
