<?php
/**
 * BannerManagement Banner管理 - 模組配置檔
 * 列表頁面模組（含 CRUD）
 * 共用既有 banners 資料表與 App\Models\Banner Model
 */
return [
    'module' => [
        'name' => 'BannerManagement',
        'name_snake' => 'banner_management',
        'name_kebab' => 'banner-management',
        'name_camel' => 'bannerManagement',
        'title' => 'Banner管理',
        'title_en' => 'Banner Management',
        'description' => 'Banner管理（含列表 CRUD）',
        'type' => 'list',
    ],
    'table' => [
        'name' => 'banners',
        'timestamps' => true,
        'soft_deletes' => false,
    ],
    'routes' => [
        'prefix' => 'banner-management',
        'name_prefix' => 'admin.banner-management',
        'permission_prefix' => 'admin.banner-management',
    ],
    'vue' => [
        'path' => 'Admin/BannerManagement',
        'form_title' => 'Banner設定',
    ],
];
