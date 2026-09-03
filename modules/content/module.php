<?php

declare(strict_types=1);

return [
    'key' => 'content',
    'label' => 'محتوى الموقع',
    'label_en' => 'Content',
    'description' => 'النصوص والأقسام العامة ثنائية اللغة',
    'version' => '1.0.0',
    'order' => 100,
    'default' => true,
    'admin' => '/admin/content.php',
    'api' => '/api/v1/content.php',
    'tables' => ['site_content'],
    'depends' => [],
];
