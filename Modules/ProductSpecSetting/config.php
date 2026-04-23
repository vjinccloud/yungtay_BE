<?php

/**
 * ProductSpecSetting 商品規格設定 - 模組配置檔
 * 
 * 多組規格搭配模組（支援笛卡爾積組合）
 * 
 * 功能：
 * - 規格群組管理（如：顏色、大小、材質…）
 * - 規格值管理（如：紅色、白色、M、L…）
 * - 自動產生規格組合（笛卡爾積）
 * - 每個組合可設定 SKU、價格、庫存
 * - 多語言支援（中文/英文）
 * - 排序、啟用/停用
 * 
 * 範例：
 *   群組「顏色」→ 紅色、白色、黃色
 *   群組「大小」→ M、L
 *   自動產生組合：紅色/M、紅色/L、白色/M、白色/L、黃色/M、黃色/L
 */

return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'ProductSpecSetting',
        'name_snake' => 'product_spec_setting',
        'name_kebab' => 'product-spec-setting',
        'name_camel' => 'productSpecSetting',
        'title' => '商品規格設定',
        'title_en' => 'Product Spec Setting',
        'description' => '商品規格管理（多組規格搭配，支援笛卡爾積自動產生組合）',
        'type' => 'list',
    ],

    // ===== 資料表設定 =====
    'tables' => [
        'spec_groups' => [
            'name' => 'spec_groups',
            'comment' => '規格群組',
            'timestamps' => true,
            'soft_deletes' => false,
        ],
        'spec_values' => [
            'name' => 'spec_values',
            'comment' => '規格值',
            'timestamps' => true,
            'soft_deletes' => false,
        ],
        'spec_combinations' => [
            'name' => 'spec_combinations',
            'comment' => '規格組合',
            'timestamps' => true,
            'soft_deletes' => false,
        ],
        'spec_combination_values' => [
            'name' => 'spec_combination_values',
            'comment' => '組合規格值對應（pivot）',
            'timestamps' => true,
            'soft_deletes' => false,
        ],
    ],

    // ===== 規格群組欄位定義 =====
    'group_fields' => [
        'name' => [
            'type' => 'translatable',
            'db_type' => 'json',
            'locales' => ['zh_TW', 'en'],
            'label' => '規格群組名稱',
            'label_en' => 'Spec Group Name',
            'placeholder' => [
                'zh_TW' => '例如：顏色、大小、材質',
                'en' => 'e.g. Color, Size, Material',
            ],
            'rules' => [
                'zh_TW' => ['required', 'string', 'max:100'],
                'en' => ['nullable', 'string', 'max:100'],
            ],
            'required' => true,
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

    // ===== 規格值欄位定義 =====
    'value_fields' => [
        'spec_group_id' => [
            'type' => 'select',
            'db_type' => 'unsignedBigInteger',
            'label' => '所屬規格群組',
            'label_en' => 'Spec Group',
            'rules' => ['required', 'integer', 'exists:spec_groups,id'],
            'required' => true,
        ],
        'name' => [
            'type' => 'translatable',
            'db_type' => 'json',
            'locales' => ['zh_TW', 'en'],
            'label' => '規格值名稱',
            'label_en' => 'Spec Value Name',
            'placeholder' => [
                'zh_TW' => '例如：紅色、M、棉質',
                'en' => 'e.g. Red, M, Cotton',
            ],
            'rules' => [
                'zh_TW' => ['required', 'string', 'max:100'],
                'en' => ['nullable', 'string', 'max:100'],
            ],
            'required' => true,
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

    // ===== 組合欄位定義 =====
    'combination_fields' => [
        'sku' => [
            'type' => 'text',
            'db_type' => 'string',
            'label' => 'SKU 編號',
            'label_en' => 'SKU',
            'rules' => ['nullable', 'string', 'max:100'],
            'required' => false,
        ],
        'price' => [
            'type' => 'number',
            'db_type' => 'decimal',
            'label' => '價格',
            'label_en' => 'Price',
            'rules' => ['nullable', 'numeric', 'min:0'],
            'required' => false,
        ],
        'stock' => [
            'type' => 'number',
            'db_type' => 'unsignedInteger',
            'label' => '庫存',
            'label_en' => 'Stock',
            'rules' => ['nullable', 'integer', 'min:0'],
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
    ],

    // ===== 系統欄位 =====
    'system_fields' => [
        'created_by' => true,
        'updated_by' => true,
    ],

    // ===== 路由設定 =====
    'routes' => [
        'prefix' => 'product-spec-settings',
        'name_prefix' => 'admin.product-spec-settings',
        'permission_prefix' => 'admin.product-spec-settings',
    ],

    // ===== Vue 設定 =====
    'vue' => [
        'path' => 'Admin/ProductSpecSetting',
        'form_title' => '商品規格設定',
    ],
];
