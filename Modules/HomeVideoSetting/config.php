<?php

/**
 * HomeVideoSetting 首頁影片管理 - 模組配置檔
 * 
 * 列表頁面模組（有 CRUD）
 * 
 * 功能：
 * - 標題（中文/英文）
 * - 影片上傳（中文版/英文版）
 * - 列表、新增、編輯、刪除
 */

return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'HomeVideoSetting',
        'name_snake' => 'home_video_setting',
        'name_kebab' => 'home-video-setting',
        'name_camel' => 'homeVideoSetting',
        'title' => '首頁影片管理',
        'title_en' => 'Home Video Management',
        'description' => '首頁影片管理（列表頁面，有 CRUD）',
        'type' => 'list',  // single = 單一設定頁面, list = 列表頁面
    ],

    // ===== 多語言設定 =====
    'locales' => [
        'zh_TW' => [
            'label' => '中文',
            'label_short' => '中',
            'suffix' => 'Zh',
            'suffix_snake' => '_zh',
        ],
        'en' => [
            'label' => '英文',
            'label_short' => '英',
            'suffix' => 'En',
            'suffix_snake' => '_en',
        ],
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'home_video_settings',
        'timestamps' => true,
        'soft_deletes' => false,
    ],

    // ===== 欄位定義 =====
    'fields' => [
        // ========== 標題欄位（多語言）==========
        'title' => [
            'type' => 'translatable',
            'db_type' => 'json',
            'label' => '標題',
            'label_en' => 'Title',
            'rules' => [
                'zh_TW' => ['required', 'string', 'max:100'],
                'en' => ['required', 'string', 'max:100'],
            ],
            'required' => true,
        ],

        // ========== 影片欄位（依語言版本）==========
        'video' => [
            'type' => 'video_localized',
            'label' => '影片',
            'label_en' => 'Video',
            'storage_path' => 'home-video-setting',
            'max_size_mb' => 100,
            'extensions' => ['mp4'],
            'required' => true,
        ],

        // ========== 排序 ==========
        'sort' => [
            'type' => 'integer',
            'label' => '排序',
            'default' => 0,
        ],

        // ========== 啟用狀態 ==========
        'is_enabled' => [
            'type' => 'boolean',
            'label' => '啟用',
            'default' => true,
        ],
    ],

    // ===== 路由設定 =====
    'routes' => [
        'prefix' => 'home-video-settings',
        'name_prefix' => 'admin.home-video-settings',
        'permission_prefix' => 'admin.home-video-settings',
    ],

    // ===== Vue 設定 =====
    'vue' => [
        'path' => 'Admin/HomeVideoSetting',
        'list_title' => '首頁影片管理',
        'form_title' => '首頁影片',
    ],

    // ===== 選單設定 =====
    'menu' => [
        'parent_id' => 11,  // 首頁系統
        'title' => '首頁影片管理',
        'icon' => 'fa-video',
        'sort' => 2,
    ],
];
