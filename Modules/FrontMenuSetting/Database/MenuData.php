<?php

namespace Modules\FrontMenuSetting\Database;

use App\Models\AdminMenu;

/**
 * FrontMenuSetting 模組 - 選單資料
 * 
 * 使用方式：
 * 1. 執行 php artisan module:install 互動式新增
 * 2. 或手動將此資料加入 AdminMenuSeeder.php
 */
class MenuData
{
    /**
     * 取得選單資料
     * 
     * @param int $startId 起始 ID
     * @param int $parentId 父層選單 ID
     * @return array
     */
    public static function getData(int $startId, int $parentId = 11): array
    {
        // 取得父層資訊
        $parent = AdminMenu::find($parentId);
        $level = $parent ? $parent->level + 1 : 0;

        return [
            [
                'id' => $startId,
                'title' => '前台選單管理',
                'parent_id' => $parentId,
                'type' => 1,           // 1 = 顯示在選單
                'level' => $level,
                'url' => 'admin/front-menu-settings',
                'url_name' => 'admin.front-menu-settings',
                'icon_image' => '',
                'status' => 1,
                'seq' => 99,
            ],
        ];
    }

    /**
     * 取得 Seeder 格式的字串（方便複製貼上）
     */
    public static function getSeederCode(int $startId, int $parentId = 11): string
    {
        $data = self::getData($startId, $parentId);
        $code = "// 前台選單管理\n";
        
        foreach ($data as $item) {
            $code .= sprintf(
                "['id' => '%d', 'title' => '%s', 'parent_id' => '%d', 'type' => '%d', 'level' => '%d', 'url' => '%s', 'url_name' => '%s', 'icon_image' => '', 'status' => '1', 'seq' => '%d'],\n",
                $item['id'],
                $item['title'],
                $item['parent_id'],
                $item['type'],
                $item['level'],
                $item['url'],
                $item['url_name'],
                $item['seq']
            );
        }

        return $code;
    }
}
