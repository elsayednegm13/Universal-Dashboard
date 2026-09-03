<?php

declare(strict_types=1);

return [
    'key' => '{{MODULE_KEY}}',
    'label' => '{{LABEL_AR}}',
    'label_en' => '{{LABEL_EN}}',
    'description' => '{{LABEL_AR}} — وحدة محتوى قابلة للتخصيص',
    'version' => '1.0.0',
    'order' => 500,
    'default' => false,
    'admin' => '/admin/{{MODULE_KEY}}.php',
    'api' => '/api/v1/{{MODULE_KEY}}.php',
    'tables' => ['{{TABLE_NAME}}'],
    'depends' => [],
];
