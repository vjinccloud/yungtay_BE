<?php

namespace Modules\RegisterBonus\Database;

use App\Models\AdminMenu;

class MenuData
{
    public static function getData(int $startId, int $parentId = 11): array
    {
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            ['id' => $startId, 'title' => '註冊購物金', 'parent_id' => $parentId, 'type' => 1, 'level' => $level, 'url' => 'admin/register-bonus', 'url_name' => 'admin.register-bonus', 'icon_image' => '', 'status' => 1, 'seq' => 16],
        ];
    }
}
