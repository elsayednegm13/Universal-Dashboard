<?php

declare(strict_types=1);

return [
    'key' => 'analytics',
    'label' => 'الإحصائيات',
    'label_en' => 'Analytics',
    'description' => 'عدادات استخدام مجمعة بدون تخزين بيانات زائر شخصية',
    'version' => '1.0.0',
    'order' => 130,
    'default' => true,
    'admin' => '/admin/analytics.php',
    'api' => '/api/v1/analytics.php',
    'tables' => ['analytics_daily'],
    'depends' => [],
];
