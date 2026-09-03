<?php

declare(strict_types=1);

return [
    'key' => 'reels',
    'label' => 'الريلز',
    'label_en' => 'Reels',
    'description' => 'فيديو قصير أو رابط خارجي',
    'version' => '1.0.0',
    'order' => 230,
    'default' => false,
    'admin' => '/admin/reels.php',
    'api' => '/api/v1/reels.php',
    'tables' => ['reels'],
    'depends' => [],
];
