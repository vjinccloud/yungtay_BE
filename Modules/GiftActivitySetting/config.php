<?php

return [
    'module' => [
        'name'        => 'GiftActivitySetting',
        'name_snake'  => 'gift_activity_setting',
        'name_kebab'  => 'gift-activity-setting',
        'name_camel'  => 'giftActivitySetting',
        'title'       => '贈品活動設定',
        'title_en'    => 'Gift Activity Setting',
        'description' => '贈品活動管理（含列表 CRUD）',
        'type'        => 'list',
    ],

    'table' => [
        'name'    => 'gift_activities',
        'comment' => '贈品活動設定',
    ],

    'routes' => [
        'prefix'      => 'gift-activity-settings',
        'name_prefix' => 'admin.gift-activity-settings',
    ],

    'vue' => [
        'path' => 'Admin/GiftActivitySetting',
    ],
];
