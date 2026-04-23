<?php
/**
 * PromotionActivity 滿額免運設定 - 模組配置檔
 * 單一設定頁面模組（無列表）
 */
return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'PromotionActivity',
        'name_snake' => 'promotion_activity',
        'name_kebab' => 'promotion-activity',
        'name_camel' => 'promotionActivity',
        'title' => '滿額免運設定',
        'title_en' => 'Free Shipping Promotion',
        'description' => '滿額免運設定（單一頁面，無列表）',
        'type' => 'single',
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'promotion_activities',
        'timestamps' => true,
        'soft_deletes' => true,
    ],

    // ===== 欄位定義 =====
    'fields' => [
        'title' => [
            'type' => 'string',
            'db_type' => 'string',
            'label' => '標題',
            'rules' => ['required', 'string', 'max:255'],
            'required' => true,
        ],
        'is_active' => [
            'type' => 'boolean',
            'db_type' => 'boolean',
            'label' => '是否啟用',
            'default' => true,
        ],
        'start_date' => [
            'type' => 'date',
            'db_type' => 'date',
            'label' => '活動開始日期',
            'rules' => ['required', 'date'],
            'required' => true,
        ],
        'end_date' => [
            'type' => 'date',
            'db_type' => 'date',
            'label' => '活動結束日期',
            'rules' => ['required', 'date', 'after_or_equal:start_date'],
            'required' => true,
        ],
        'min_amount' => [
            'type' => 'integer',
            'db_type' => 'unsignedInteger',
            'label' => '滿額金額',
            'rules' => ['required', 'integer', 'min:0'],
            'required' => true,
        ],
        'discount_amount' => [
            'type' => 'integer',
            'db_type' => 'unsignedInteger',
            'label' => '抵扣金額',
            'rules' => ['required', 'integer', 'min:0'],
            'required' => true,
        ],
        'category_ids' => [
            'type' => 'json',
            'db_type' => 'json',
            'label' => '指定商品分類',
            'rules' => ['nullable', 'array'],
        ],
    ],

    // ===== 系統欄位 =====
    'system_fields' => [
        'created_by' => true,
        'updated_by' => true,
    ],

    // ===== 路由設定 =====
    'routes' => [
        'prefix' => 'promotion-activity',
        'name_prefix' => 'admin.promotion-activity',
        'permission_prefix' => 'admin.promotion-activity',
    ],

    // ===== Vue 設定 =====
    'vue' => [
        'path' => 'Admin/PromotionActivity',
        'form_title' => '滿額免運設定',
    ],
];
