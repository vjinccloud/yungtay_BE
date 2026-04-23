<?php

/**
 * HomeVideoSetting 選單資料
 * 
 * 包含：列表、新增、編輯、刪除
 */

return [
    // 主選單
    [
        'id' => 138,
        'parent_id' => 11,  // 首頁系統
        'title' => '首頁影片管理',
        'url' => 'admin/home-video-settings',
        'url_name' => 'admin.home-video-settings',
        'type' => 1,  // 1 = 顯示在選單
        'level' => 1,
        'icon' => '',
        'sort' => 2,
        'is_enabled' => 1,
    ],
    // 新增
    [
        'id' => 139,
        'parent_id' => 138,
        'title' => '新增首頁影片',
        'url' => 'admin/home-video-settings/add',
        'url_name' => 'admin.home-video-settings.add',
        'type' => 0,  // 0 = 不顯示在選單（權限用）
        'level' => 2,
        'icon' => '',
        'sort' => 1,
        'is_enabled' => 1,
    ],
    // 編輯
    [
        'id' => 140,
        'parent_id' => 138,
        'title' => '編輯首頁影片',
        'url' => 'admin/home-video-settings/edit',
        'url_name' => 'admin.home-video-settings.edit',
        'type' => 0,
        'level' => 2,
        'icon' => '',
        'sort' => 2,
        'is_enabled' => 1,
    ],
    // 刪除
    [
        'id' => 141,
        'parent_id' => 138,
        'title' => '刪除首頁影片',
        'url' => '',
        'url_name' => 'admin.home-video-settings.delete',
        'type' => 0,
        'level' => 2,
        'icon' => '',
        'sort' => 3,
        'is_enabled' => 1,
    ],
];
