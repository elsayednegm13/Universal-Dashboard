<?php

declare(strict_types=1);

return [
    'key' => 'contact',
    'label' => 'الرسائل والتواصل',
    'label_en' => 'Contact',
    'description' => 'نماذج التواصل والرسائل وإعدادات القنوات',
    'version' => '1.0.0',
    'order' => 120,
    'default' => true,
    'admin' => '/admin/contact.php',
    'api' => '/api/v1/contact.php',
    'tables' => ['contact_messages','contact_rate_limits'],
    'depends' => [],
];
