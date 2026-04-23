<?php

namespace Modules\ProductListing\Database;

use App\Models\AdminMenu;

class MenuData
{
    public static function getData(int $startId, int $parentId = 11): array
    {
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            ['id' => $startId,     'title' => '商品上架管理', 'parent_id' => $parentId, 'type' => 1, 'level' => $level,     'url' => 'admin/product-listings',        'url_name' => 'admin.product-listings.index',   'icon_image' => '', 'status' => 1, 'seq' => 12],
            ['id' => $startId + 1, 'title' => '新增商品',     'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/product-listings/create',  'url_name' => 'admin.product-listings.create',  'icon_image' => '', 'status' => 1, 'seq' => 1],
            ['id' => $startId + 2, 'title' => '編輯商品',     'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => 'admin/product-listings/edit',    'url_name' => 'admin.product-listings.edit',    'icon_image' => '', 'status' => 1, 'seq' => 2],
            ['id' => $startId + 3, 'title' => '刪除商品',     'parent_id' => $startId,  'type' => 0, 'level' => $level + 1, 'url' => '',                               'url_name' => 'admin.product-listings.destroy', 'icon_image' => '', 'status' => 1, 'seq' => 3],
        ];
    }
}
