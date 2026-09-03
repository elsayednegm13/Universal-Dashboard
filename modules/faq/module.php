<?php

declare(strict_types=1);

return [
    'key' => 'faq',
    'label' => 'الأسئلة الشائعة',
    'label_en' => 'FAQ',
    'description' => 'FAQ ثنائي اللغة',
    'version' => '1.0.0',
    'order' => 200,
    'default' => false,
    'admin' => '/admin/faq.php',
    'api' => '/api/v1/faq.php',
    'tables' => ['faqs'],
    'depends' => [],
];
