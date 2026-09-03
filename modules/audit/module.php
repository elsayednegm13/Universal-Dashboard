<?php

declare(strict_types=1);

return [
    'key' => 'audit',
    'label' => 'سجل النشاط',
    'label_en' => 'Audit Log',
    'description' => 'تدقيق عمليات الإدارة',
    'version' => '1.0.0',
    'order' => 140,
    'default' => true,
    'admin' => '/admin/audit.php',
    'api' => null,
    'tables' => ['audit_logs'],
    'depends' => [],
];
