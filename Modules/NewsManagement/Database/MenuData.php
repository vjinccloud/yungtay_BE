<?php

namespace Modules\NewsManagement\Database;

use App\Models\AdminMenu;

class MenuData
{
    /**
     * 最新消息管理選單資料
     * 掛載在「其他管理系統」(parent_id = 62) 之下
     */
    public static function getData(int $startId, int $parentId = 62): array
    {
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            ['id' => $startId,     'title' => '最新消息管理', 'parent_id' => $parentId, 'type' => 1, 'level' => $level,     'url' => 'admin/news-management',        'url_name' => 'admin.news-management.index',   'icon_image' => '', 'status' => 1, 'seq' => 19],
            ['id' => $startId + 1, 'title' => '新增最新消息', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/news-management/create',  'url_name' => 'admin.news-management.create',  'icon_image' => '', 'status' => 1, 'seq' => 1],
            ['id' => $startId + 2, 'title' => '編輯最新消息', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/news-management/edit',    'url_name' => 'admin.news-management.edit',    'icon_image' => '', 'status' => 1, 'seq' => 2],
            ['id' => $startId + 3, 'title' => '刪除最新消息', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => '',                              'url_name' => 'admin.news-management.destroy', 'icon_image' => '', 'status' => 1, 'seq' => 3],
        ];
    }
}
