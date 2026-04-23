<?php

/**
 * IntroVideo 片頭動畫 - 模組配置檔
 * 
 * 單一設定頁面模組（無列表）
 * 
 * 功能：
 * - 上傳單一 MP4 影片作為網站片頭動畫
 */

return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'IntroVideo',
        'name_snake' => 'intro_video',
        'name_kebab' => 'intro-video',
        'name_camel' => 'introVideo',
        'title' => '片頭動畫',
        'title_en' => 'Intro Video',
        'description' => '片頭動畫設定（單一頁面，上傳 MP4 影片）',
        'type' => 'single',  // single = 單一設定頁面
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'intro_videos',
        'timestamps' => true,
        'soft_deletes' => false,
    ],

    // ===== 欄位定義 =====
    'fields' => [
        // ========== 影片欄位 ==========
        'video' => [
            'type' => 'video',
            'label' => '片頭動畫影片',
            'label_en' => 'Intro Video',
            'storage_path' => 'intro-video',
            'max_size_mb' => 100,
            'allowed_types' => ['video/mp4'],
            'required' => true,
        ],

        // ========== 狀態欄位 ==========
        'is_active' => [
            'type' => 'boolean',
            'label' => '啟用狀態',
            'label_en' => 'Active Status',
            'default' => true,
        ],
    ],

    // ===== 選單設定 =====
    'menu' => [
        'parent_id' => 11,  // 首頁系統
        'icon' => '',
        'seq' => 4,
    ],
];
