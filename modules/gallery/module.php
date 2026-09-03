<?php

declare(strict_types=1);

return [
    'key' => 'gallery',
    'label' => 'المعرض',
    'label_en' => 'Gallery',
    'description' => 'Gallery صور قابل للترتيب والإخفاء',
    'version' => '1.0.0',
    'order' => 220,
    'default' => false,
    'admin' => '/admin/gallery.php',
    'api' => '/api/v1/gallery.php',
    'tables' => ['gallery_items'],
    'depends' => [],
];
