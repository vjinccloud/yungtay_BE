<?php

namespace Modules\BannerManagement\Database;

use App\Models\AdminMenu;

class MenuData
{
    public static function getData(int $startId, int $parentId = 11): array
    {
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            ['id' => $startId,     'title' => 'Banner管理',   'parent_id' => $parentId, 'type' => 1, 'level' => $level,     'url' => 'admin/banner-management',        'url_name' => 'admin.banner-management.index',   'icon_image' => '', 'status' => 1, 'seq' => 18],
            ['id' => $startId + 1, 'title' => '新增Banner',   'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/banner-management/create',  'url_name' => 'admin.banner-management.create',  'icon_image' => '', 'status' => 1, 'seq' => 1],
            ['id' => $startId + 2, 'title' => '編輯Banner',   'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/banner-management/edit',    'url_name' => 'admin.banner-management.edit',    'icon_image' => '', 'status' => 1, 'seq' => 2],
            ['id' => $startId + 3, 'title' => '刪除Banner',   'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => '',                                'url_name' => 'admin.banner-management.destroy', 'icon_image' => '', 'status' => 1, 'seq' => 3],
        ];
    }
}
