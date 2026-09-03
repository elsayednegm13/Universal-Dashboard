<?php

declare(strict_types=1);

return [
    'key' => 'backups',
    'label' => 'النسخ الاحتياطية',
    'label_en' => 'Backup / Restore',
    'description' => 'Backup / Restore لبيانات النظام والملفات',
    'version' => '1.0.0',
    'order' => 150,
    'default' => true,
    'admin' => '/admin/backups.php',
    'api' => null,
    'tables' => [],
    'depends' => [],
];
