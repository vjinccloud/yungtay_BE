<?php

/**
 * SalesLocationImage 銷售據點圖片管理 - 模組配置檔
 * 
 * 列表頁面模組
 * 
 * 功能：
 * - 上傳銷售據點圖片（中文版/英文版）
 */

return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'SalesLocationImage',
        'name_snake' => 'sales_location_image',
        'name_kebab' => 'sales-location-images',
        'name_camel' => 'salesLocationImage',
        'title' => '銷售據點圖片管理',
        'title_en' => 'Sales Location Image',
        'description' => '銷售據點圖片管理（列表頁面）',
        'type' => 'list',
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'sales_location_images',
        'timestamps' => true,
        'soft_deletes' => false,
    ],

    // ===== 欄位定義 =====
    'fields' => [
        'title' => [
            'type' => 'translatable',
            'label' => '標題',
            'required' => false,
        ],
        'image_zh' => [
            'type' => 'image',
            'label' => '圖片（中文版）',
            'required' => true,
        ],
        'image_en' => [
            'type' => 'image',
            'label' => '圖片（英文版）',
            'required' => true,
        ],
    ],

    // ===== 選單設定 =====
    'menu' => [
        'parent_id' => 11,  // 首頁系統
        'icon' => '',
        'seq' => 5,
    ],
];
