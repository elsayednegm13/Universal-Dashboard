<?php

declare(strict_types=1);

return [
    'key' => 'catalog',
    'label' => 'كتالوج المنتجات',
    'label_en' => 'Catalog',
    'description' => 'ماركات وتصنيفات ومنتجات وحالات وCSV وسلة',
    'version' => '1.0.0',
    'order' => 240,
    'default' => false,
    'admin' => '/admin/catalog.php',
    'api' => '/api/v1/catalog.php',
    'tables' => ['catalog_brands','catalog_categories','catalog_products'],
    'depends' => [],
];
