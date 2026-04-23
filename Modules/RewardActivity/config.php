<?php
/**
 * RewardActivity 回饋活動 - 模組配置檔
 * 列表頁面模組（含 CRUD）
 */
return [
    // ===== 模組基本資訊 =====
    'module' => [
        'name' => 'RewardActivity',
        'name_snake' => 'reward_activity',
        'name_kebab' => 'reward-activity',
        'name_camel' => 'rewardActivity',
        'title' => '回饋活動',
        'title_en' => 'Reward Activity',
        'description' => '回饋活動管理（含列表 CRUD）',
        'type' => 'list',
    ],

    // ===== 資料表設定 =====
    'table' => [
        'name' => 'reward_activities',
        'timestamps' => true,
        'soft_deletes' => true,
    ],

    // ===== 路由設定 =====
    'routes' => [
        'prefix' => 'reward-activities',
        'name_prefix' => 'admin.reward-activities',
        'permission_prefix' => 'admin.reward-activities',
    ],

    // ===== Vue 設定 =====
    'vue' => [
        'path' => 'Admin/RewardActivity',
        'form_title' => '回饋活動設定',
    ],
];
