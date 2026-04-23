<?php

return [
    'name'  => 'ProductListing',
    'title' => '商品上架管理',
    'type'  => 'crud',

    'tables' => [
        'products'       => '商品主表',
        'product_images' => '商品圖片',
        'product_skus'   => '商品 SKU（規格值組合）',
    ],

    'routes' => [
        'prefix'      => 'product-listings',
        'name_prefix' => 'admin.product-listings',
    ],

    'vue' => [
        'path' => 'Admin/ProductListing',
    ],

    'features' => [
        'tabs' => [
            '商品簡介' => '商品基本資訊、主圖、多圖上傳',
            '商品規格' => '選擇規格組合 → 自動產生 SKU 矩陣（價格/庫存/SKU 編號）',
            '介紹編輯器' => 'CKEditor 富文字編輯器',
        ],
    ],
];
