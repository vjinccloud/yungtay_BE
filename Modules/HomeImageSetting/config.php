<?php

/**
 * HomeImageSetting 首頁圖片設定 - 模組配置檔
 * 
 * 單一設定頁面模組（無列表）
 * 
 * 功能：
 * - 標題（中文/英文）
 * - 圖片（中文版/英文版）
 */

return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'HomeImageSetting',
        'name_snake' => 'home_image_setting',
        'name_kebab' => 'home-image-setting',
        'name_camel' => 'homeImageSetting',
        'title' => '首頁圖片設定',
        'title_en' => 'Home Image Setting',
        'description' => '首頁圖片設定（單一頁面，無列表）',
        'type' => 'single',  // single = 單一設定頁面, list = 列表頁面
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'home_image_settings',
        'timestamps' => true,
        'soft_deletes' => false,
    ],

    // ===== 欄位定義 =====
    'fields' => [
        // ========== 圖片欄位（中文版）==========
        'image_zh' => [
            'type' => 'image',
            'label' => '圖片（中文版）',
            'label_en' => 'Image (Chinese)',
            'image_type' => 'image_zh',
            'storage_path' => 'home-image-setting/zh',
            'width' => 800,
            'height' => 600,
            'ratio' => '800:600',
            'max_size_mb' => 5,
            'slim_label' => '圖片拖移至此，建議尺寸 800px * 600px',
            'required' => true,
        ],

        // ========== 圖片欄位（英文版）==========
        'image_en' => [
            'type' => 'image',
            'label' => '圖片（英文版）',
            'label_en' => 'Image (English)',
            'image_type' => 'image_en',
            'storage_path' => 'home-image-setting/en',
            'width' => 800,
            'height' => 600,
            'ratio' => '800:600',
            'max_size_mb' => 5,
            'slim_label' => 'Drop image here, recommended size 800px * 600px',
            'required' => true,
        ],

        // ========== 標題欄位（多語言）==========
        'title' => [
            'type' => 'translatable',
            'db_type' => 'json',
            'locales' => ['zh_TW', 'en'],
            'label' => '標題',
            'label_en' => 'Title',
            'placeholder' => [
                'zh_TW' => '請輸入中文標題',
                'en' => 'Please enter English title',
            ],
            'rules' => [
                'zh_TW' => ['required', 'string', 'max:100'],
                'en' => ['required', 'string', 'max:100'],
            ],
            'required' => true,
        ],
    ],

    // ===== 系統欄位 =====
    'system_fields' => [
        'created_by' => true,
        'updated_by' => true,
    ],

    // ===== 路由設定 =====
    'routes' => [
        'prefix' => 'home-image-setting',
        'name_prefix' => 'admin.home-image-setting',
        'permission_prefix' => 'admin.home-image-setting',
    ],

    // ===== Vue 設定 =====
    'vue' => [
        'path' => 'Admin/HomeImageSetting',
        'form_title' => '首頁圖片設定',
    ],
];
