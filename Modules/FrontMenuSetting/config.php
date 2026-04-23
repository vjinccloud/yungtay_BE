<?php

/**
 * FrontMenuSetting 前台選單管理 - 模組配置檔
 * 
 * 列表型 CRUD 模組（層級式選單管理）
 * 
 * 功能：
 * - 選單 CRUD（新增、編輯、刪除）
 * - 樹狀結構（parent_id）
 * - 多語言標題（中文/英文）
 * - 連結類型（外部連結/內部路由/頁面/無連結）
 * - 排序、啟用/停用
 */

return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'FrontMenuSetting',
        'name_snake' => 'front_menu_setting',
        'name_kebab' => 'front-menu-setting',
        'name_camel' => 'frontMenuSetting',
        'title' => '前台選單管理',
        'title_en' => 'Front Menu Setting',
        'description' => '前台選單管理（層級式 CRUD 列表頁面）',
        'type' => 'list',
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'front_menus',
        'timestamps' => true,
        'soft_deletes' => false,
    ],

    // ===== 欄位定義 =====
    'fields' => [
        'parent_id' => [
            'type' => 'select',
            'db_type' => 'unsignedBigInteger',
            'label' => '父層選單',
            'label_en' => 'Parent Menu',
            'rules' => ['required', 'integer', 'min:0'],
            'required' => true,
            'default' => 0,
        ],
        'title' => [
            'type' => 'translatable',
            'db_type' => 'json',
            'locales' => ['zh_TW', 'en'],
            'label' => '選單名稱',
            'label_en' => 'Menu Title',
            'placeholder' => [
                'zh_TW' => '請輸入中文名稱',
                'en' => 'Please enter English title',
            ],
            'rules' => [
                'zh_TW' => ['required', 'string', 'max:100'],
                'en' => ['required', 'string', 'max:100'],
            ],
            'required' => true,
        ],
        'link_type' => [
            'type' => 'select',
            'db_type' => 'string',
            'label' => '連結類型',
            'label_en' => 'Link Type',
            'options' => [
                'none' => '無連結（純分類）',
                'url' => '外部連結',
                'route' => '內部路由',
                'page' => '頁面',
            ],
            'rules' => ['required', 'string', 'in:url,route,page,none'],
            'required' => true,
            'default' => 'none',
        ],
        'link_url' => [
            'type' => 'text',
            'db_type' => 'string',
            'label' => '連結網址',
            'label_en' => 'Link URL',
            'rules' => ['nullable', 'string', 'max:500'],
            'required' => false,
        ],
        'link_target' => [
            'type' => 'select',
            'db_type' => 'string',
            'label' => '開啟方式',
            'label_en' => 'Link Target',
            'options' => [
                '_self' => '同分頁開啟',
                '_blank' => '新分頁開啟',
            ],
            'rules' => ['required', 'string', 'in:_self,_blank'],
            'required' => true,
            'default' => '_self',
        ],
        'icon' => [
            'type' => 'text',
            'db_type' => 'string',
            'label' => '圖標',
            'label_en' => 'Icon',
            'rules' => ['nullable', 'string', 'max:100'],
            'required' => false,
        ],
        'seq' => [
            'type' => 'number',
            'db_type' => 'unsignedSmallInteger',
            'label' => '排序',
            'label_en' => 'Sort Order',
            'rules' => ['nullable', 'integer', 'min:0'],
            'default' => 0,
        ],
        'status' => [
            'type' => 'boolean',
            'db_type' => 'boolean',
            'label' => '啟用狀態',
            'label_en' => 'Status',
            'rules' => ['nullable', 'boolean'],
            'default' => true,
        ],
    ],

    // ===== 系統欄位 =====
    'system_fields' => [
        'created_by' => true,
        'updated_by' => true,
    ],

    // ===== 路由設定 =====
    'routes' => [
        'prefix' => 'front-menu-settings',
        'name_prefix' => 'admin.front-menu-settings',
        'permission_prefix' => 'admin.front-menu-settings',
    ],

    // ===== Vue 設定 =====
    'vue' => [
        'path' => 'Admin/FrontMenuSetting',
        'form_title' => '前台選單管理',
    ],
];
