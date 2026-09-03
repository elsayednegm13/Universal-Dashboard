<?php

declare(strict_types=1);

return [
    'key' => 'partners',
    'label' => 'الشركاء',
    'label_en' => 'Partners',
    'description' => 'شعارات وروابط الشركاء',
    'version' => '1.0.0',
    'order' => 210,
    'default' => false,
    'admin' => '/admin/partners.php',
    'api' => '/api/v1/partners.php',
    'tables' => ['partners'],
    'depends' => [],
];
