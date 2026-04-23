<?php

namespace Modules\FrontMenuSetting\Database;

use Illuminate\Console\Command;
use App\Models\AdminMenu;
use Illuminate\Support\Facades\DB;

/**
 * FrontMenuSetting 模組 - 選單 Seeder 指令
 * 
 * 使用方式：
 * php artisan front-menu:seed-admin-menu
 */
class SeedAdminMenuCommand extends Command
{
    protected $signature = 'front-menu:seed-admin-menu 
                            {--parent=62 : 父層選單 ID（預設: 62 其他管理系統）}
                            {--force : 強制重新寫入（會先刪除舊的再新增）}';

    protected $description = '新增「前台選單管理」到後台選單';

    public function handle()
    {
        $parentId = (int) $this->option('parent');
        $force = $this->option('force');

        // 檢查是否已存在
        $existing = AdminMenu::where('url_name', 'admin.front-menu-settings.index')
            ->orWhere('url_name', 'admin.front-menu-settings')
            ->first();

        if ($existing && !$force) {
            $this->warn("⚠️  「前台選單管理」選單已存在（ID: {$existing->id}）");
            $this->info("   如需重新寫入，請加上 --force 參數");
            return 0;
        }

        // 強制模式：先刪除舊的
        if ($existing && $force) {
            $this->deleteExistingMenus();
            $this->info('🗑️  已刪除舊的選單資料');
        }

        // 取得父層資訊
        $parent = AdminMenu::find($parentId);
        if (!$parent) {
            $this->error("❌ 找不到父層選單 ID: {$parentId}");
            $this->info("   可用的頂層選單：");
            AdminMenu::where('parent_id', 0)->where('type', 1)->get()->each(function ($menu) {
                $this->line("   [{$menu->id}] {$menu->title}");
            });
            return 1;
        }

        $level = $parent->level + 1;

        // 顯示將要新增的位置
        $this->info("📍 將新增到：{$parent->title}（ID: {$parent->id}）底下");
        $this->newLine();

        DB::beginTransaction();

        try {
            // 取得下一個可用 ID
            $nextId = AdminMenu::max('id') + 1;

            $menuItems = [
                // 主選單：前台選單管理
                [
                    'id' => $nextId,
                    'title' => '前台選單管理',
                    'parent_id' => $parentId,
                    'type' => 1,
                    'level' => $level,
                    'url' => 'admin/front-menu-settings',
                    'url_name' => 'admin.front-menu-settings.index',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 10,
                ],
                // 子選單（隱藏）：新增
                [
                    'id' => $nextId + 1,
                    'title' => '新增前台選單',
                    'parent_id' => $nextId,
                    'type' => 0,
                    'level' => $level + 1,
                    'url' => 'admin/front-menu-settings/add',
                    'url_name' => 'admin.front-menu-settings.add',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 1,
                ],
                // 子選單（隱藏）：編輯
                [
                    'id' => $nextId + 2,
                    'title' => '編輯前台選單',
                    'parent_id' => $nextId,
                    'type' => 0,
                    'level' => $level + 1,
                    'url' => 'admin/front-menu-settings/edit',
                    'url_name' => 'admin.front-menu-settings.edit',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 2,
                ],
                // 子選單（隱藏）：刪除
                [
                    'id' => $nextId + 3,
                    'title' => '刪除前台選單',
                    'parent_id' => $nextId,
                    'type' => 0,
                    'level' => $level + 1,
                    'url' => '',
                    'url_name' => 'admin.front-menu-settings.destroy',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 3,
                ],
            ];

            // 批次寫入
            AdminMenu::upsert(
                $menuItems,
                ['id'],
                ['title', 'parent_id', 'type', 'level', 'url', 'url_name', 'icon_image', 'status', 'seq']
            );

            DB::commit();

            $this->info('✅ 已成功新增以下選單：');
            $this->newLine();

            foreach ($menuItems as $item) {
                $typeLabel = $item['type'] == 1 ? '顯示' : '隱藏';
                $indent = str_repeat('  ', $item['level']);
                $this->line("   {$indent}[{$item['id']}] {$item['title']} ({$typeLabel}) - {$item['url_name']}");
            }

            $this->newLine();
            $this->info('💡 提示：如需同步權限，請執行 php artisan module:install --sync-permissions');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ 新增失敗：' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 刪除已存在的前台選單管理相關選單
     */
    protected function deleteExistingMenus()
    {
        $urlNames = [
            'admin.front-menu-settings.index',
            'admin.front-menu-settings',
            'admin.front-menu-settings.add',
            'admin.front-menu-settings.edit',
            'admin.front-menu-settings.destroy',
        ];

        // 找到主選單
        $mainMenu = AdminMenu::where('url_name', 'admin.front-menu-settings.index')
            ->orWhere('url_name', 'admin.front-menu-settings')
            ->first();

        if ($mainMenu) {
            // 刪除所有子選單
            AdminMenu::where('parent_id', $mainMenu->id)->delete();
            // 刪除主選單
            $mainMenu->delete();
        }

        // 額外清理可能殘留的
        AdminMenu::whereIn('url_name', $urlNames)->delete();
    }
}
