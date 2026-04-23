<?php

namespace Modules\SalesLocationImage\Database;

use App\Models\AdminMenu;

/**
 * SalesLocationImage 模組 - 選單資料
 */
class MenuData
{
    public static function getData(int $startId, int $parentId = 11): array
    {
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            [
                'id' => $startId,
                'title' => '銷售據點圖片管理',
                'parent_id' => $parentId,
                'type' => 1,
                'level' => $level,
                'url' => 'admin/sales-location-images',
                'url_name' => 'admin.sales-location-images.index',
                'icon_image' => '',
                'status' => 1,
                'seq' => 5,
            ],
            [
                'id' => $startId + 1,
                'title' => '新增銷售據點圖片',
                'parent_id' => $startId,
                'type' => 0,
                'level' => $level + 1,
                'url' => 'admin/sales-location-images/add',
                'url_name' => 'admin.sales-location-images.add',
                'icon_image' => '',
                'status' => 1,
                'seq' => 1,
            ],
            [
                'id' => $startId + 2,
                'title' => '編輯銷售據點圖片',
                'parent_id' => $startId,
                'type' => 0,
                'level' => $level + 1,
                'url' => 'admin/sales-location-images/edit',
                'url_name' => 'admin.sales-location-images.edit',
                'icon_image' => '',
                'status' => 1,
                'seq' => 2,
            ],
            [
                'id' => $startId + 3,
                'title' => '刪除銷售據點圖片',
                'parent_id' => $startId,
                'type' => 0,
                'level' => $level + 1,
                'url' => '',
                'url_name' => 'admin.sales-location-images.destroy',
                'icon_image' => '',
                'status' => 1,
                'seq' => 3,
            ],
        ];
    }
}
