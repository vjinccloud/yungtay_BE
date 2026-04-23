<?php

namespace Modules\PromotionActivity\Database;

use App\Models\AdminMenu;

class MenuData
{
    public static function getData(int $startId, int $parentId = 11): array
    {
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            ['id' => $startId, 'title' => '滿額免運設定', 'parent_id' => $parentId, 'type' => 1, 'level' => $level, 'url' => 'admin/promotion-activity', 'url_name' => 'admin.promotion-activity', 'icon_image' => '', 'status' => 1, 'seq' => 15],
        ];
    }
}
