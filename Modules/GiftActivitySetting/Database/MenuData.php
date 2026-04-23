<?php

namespace Modules\GiftActivitySetting\Database;

use App\Models\AdminMenu;

class MenuData
{
    public static function getData(int $startId, int $parentId = 11): array
    {
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            ['id' => $startId,     'title' => '贈品活動設定', 'parent_id' => $parentId, 'type' => 1, 'level' => $level,     'url' => 'admin/gift-activity-settings',        'url_name' => 'admin.gift-activity-settings.index',   'icon_image' => '', 'status' => 1, 'seq' => 18],
            ['id' => $startId + 1, 'title' => '新增贈品活動', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/gift-activity-settings/create',  'url_name' => 'admin.gift-activity-settings.create',  'icon_image' => '', 'status' => 1, 'seq' => 1],
            ['id' => $startId + 2, 'title' => '編輯贈品活動', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/gift-activity-settings/edit',    'url_name' => 'admin.gift-activity-settings.edit',    'icon_image' => '', 'status' => 1, 'seq' => 2],
            ['id' => $startId + 3, 'title' => '刪除贈品活動', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => '',                                     'url_name' => 'admin.gift-activity-settings.destroy', 'icon_image' => '', 'status' => 1, 'seq' => 3],
        ];
    }
}
