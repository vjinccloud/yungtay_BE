<?php

namespace Modules\IntroVideo\Database;

use App\Models\AdminMenu;

/**
 * IntroVideo 模組 - 選單資料
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
                'title' => '片頭動畫',
                'parent_id' => $parentId,
                'type' => 1,           // 1 = 顯示在選單
                'level' => $level,
                'url' => 'admin/intro-video',
                'url_name' => 'admin.intro-video',
                'icon_image' => '',
                'status' => 1,
                'seq' => 4,
            ],
        ];
    }

    /**
     * 取得 Seeder 格式的字串（方便複製貼上）
     */
    public static function getSeederCode(int $startId, int $parentId = 11): string
    {
        $data = self::getData($startId, $parentId);
        
        $code = "// 片頭動畫\n";
        foreach ($data as $item) {
            $code .= "[" .
                "'id' => '{$item['id']}', " .
                "'title' => '{$item['title']}', " .
                "'parent_id' => '{$item['parent_id']}', " .
                "'type' => '{$item['type']}', " .
                "'level' => '{$item['level']}', " .
                "'url' => '{$item['url']}', " .
                "'url_name' => '{$item['url_name']}', " .
                "'icon_image' => '{$item['icon_image']}', " .
                "'status' => '{$item['status']}', " .
                "'seq' => '{$item['seq']}'" .
                "],\n";
        }
        
        return $code;
    }
}
