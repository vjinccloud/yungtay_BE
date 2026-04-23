<?php

namespace Modules\RewardActivity\Database;

use App\Models\AdminMenu;

class MenuData
{
    public static function getData(int $startId, int $parentId = 11): array
    {
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            ['id' => $startId,     'title' => '回饋活動管理', 'parent_id' => $parentId, 'type' => 1, 'level' => $level,     'url' => 'admin/reward-activities',        'url_name' => 'admin.reward-activities.index',   'icon_image' => '', 'status' => 1, 'seq' => 17],
            ['id' => $startId + 1, 'title' => '新增回饋活動', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/reward-activities/create',  'url_name' => 'admin.reward-activities.create',  'icon_image' => '', 'status' => 1, 'seq' => 1],
            ['id' => $startId + 2, 'title' => '編輯回饋活動', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/reward-activities/edit',    'url_name' => 'admin.reward-activities.edit',    'icon_image' => '', 'status' => 1, 'seq' => 2],
            ['id' => $startId + 3, 'title' => '刪除回饋活動', 'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => '',                                'url_name' => 'admin.reward-activities.destroy', 'icon_image' => '', 'status' => 1, 'seq' => 3],
        ];
    }
}
