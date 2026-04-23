<?php

/**
 * MenuSetting 選單管理 - 模組配置檔
 * 
 * 列表型 CRUD 模組
 * 
 * 功能：
 * - 選單 CRUD（新增、編輯、刪除）
 * - 樹狀結構（parent_id）
 * - 排序、啟用/停用
 */

return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'MenuSetting',
        'name_snake' => 'menu_setting',
        'name_kebab' => 'menu-setting',
        'name_camel' => 'menuSetting',
        'title' => '選單管理',
        'title_en' => 'Menu Setting',
        'description' => '後台選單管理（CRUD 列表頁面）',
        'type' => 'list',  // single = 單一設定頁面, list = 列表頁面
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'admin_menu',
        'timestamps' => true,
        'soft_deletes' => false,
    ],

    // ===== 欄位定義 =====
    'fields' => [
        'title' => [
            'type' => 'text',
            'db_type' => 'string',
            'label' => '選單名稱',
            'label_en' => 'Menu Title',
            'rules' => ['required', 'string', 'max:255'],
            'required' => true,
        ],
        'parent_id' => [
            'type' => 'select',
            'db_type' => 'smallInteger',
            'label' => '父層選單',
            'label_en' => 'Parent Menu',
            'rules' => ['required', 'integer'],
            'required' => true,
            'default' => 0,
        ],
        'type' => [
            'type' => 'select',
            'db_type' => 'unsignedTinyInteger',
            'label' => '顯示類型',
            'label_en' => 'Display Type',
            'options' => [
                0 => '不顯示',
                1 => '顯示在選單',
            ],
            'rules' => ['required', 'integer', 'in:0,1'],
            'required' => true,
        ],
        'url' => [
            'type' => 'text',
            'db_type' => 'string',
            'label' => '連結網址',
            'label_en' => 'URL',
            'rules' => ['nullable', 'string', 'max:255'],
            'required' => false,
        ],
        'url_name' => [
            'type' => 'text',
            'db_type' => 'string',
            'label' => '路由名稱',
            'label_en' => 'Route Name',
            'rules' => ['nullable', 'string', 'max:255'],
            'required' => false,
        ],
        'icon_image' => [
            'type' => 'text',
            'db_type' => 'string',
            'label' => '圖標',
            'label_en' => 'Icon',
            'rules' => ['nullable', 'string', 'max:255'],
            'required' => false,
        ],
        'status' => [
            'type' => 'boolean',
            'db_type' => 'boolean',
            'label' => '啟用狀態',
            'label_en' => 'Status',
            'rules' => ['nullable', 'boolean'],
            'default' => true,
        ],
        'seq' => [
            'type' => 'number',
            'db_type' => 'unsignedTinyInteger',
            'label' => '排序',
            'label_en' => 'Sort Order',
            'rules' => ['nullable', 'integer', 'min:0'],
            'default' => 0,
        ],
    ],

    // ===== 路由設定 =====
    'routes' => [
        'prefix' => 'menu-settings',
        'name_prefix' => 'admin.menu-settings',
        'permission_prefix' => 'admin.menu-settings',
    ],

    // ===== Vue 設定 =====
    'vue' => [
        'path' => 'Admin/MenuSetting',
        'form_title' => '選單管理',
    ],
];
