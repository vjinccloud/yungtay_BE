<?php
/**
 * RegisterBonus 註冊購物金 - 模組配置檔
 * 單一設定頁面模組（無列表）
 */
return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'RegisterBonus',
        'name_snake' => 'register_bonus',
        'name_kebab' => 'register-bonus',
        'name_camel' => 'registerBonus',
        'title' => '註冊購物金',
        'title_en' => 'Register Bonus',
        'description' => '註冊贈送購物金設定（單一頁面，無列表）',
        'type' => 'single',
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'register_bonus_settings',
        'timestamps' => true,
        'soft_deletes' => false,
    ],

    // ===== 欄位定義 =====
    'fields' => [
        'is_active' => [
            'type' => 'boolean',
            'label' => '啟用狀態',
            'default' => true,
        ],
        'bonus_amount' => [
            'type' => 'integer',
            'label' => '贈送數值',
            'rules' => ['required', 'integer', 'min:0'],
        ],
        'expiry_type' => [
            'type' => 'string',
            'label' => '有效期限',
            'rules' => ['required', 'in:unlimited,days'],
        ],
        'expiry_days' => [
            'type' => 'integer',
            'label' => '有效天數',
            'rules' => ['nullable', 'integer', 'min:1'],
        ],
    ],

    // ===== 系統欄位 =====
    'system_fields' => [
        'created_by' => true,
        'updated_by' => true,
    ],

    // ===== 路由設定 =====
    'routes' => [
        'prefix' => 'register-bonus',
        'name_prefix' => 'admin.register-bonus',
        'permission_prefix' => 'admin.register-bonus',
    ],

    // ===== Vue 設定 =====
    'vue' => [
        'path' => 'Admin/RegisterBonus',
        'form_title' => '註冊購物金設定',
    ],
];
