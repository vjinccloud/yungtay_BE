<?php

namespace Modules\OrderManagement\Database;

use Illuminate\Console\Command;
use App\Models\AdminMenu;
use Illuminate\Support\Facades\DB;

/**
 * OrderManagement 模組 - 選單 Seeder 指令
 * 
 * 使用方式：
 * php artisan order:seed-admin-menu
 */
class SeedAdminMenuCommand extends Command
{
    protected $signature = 'order:seed-admin-menu 
                            {--parent=164 : 父層選單 ID（預設: 164 商品管理）}
                            {--force : 強制重新寫入}';

    protected $description = '新增「訂單管理」到後台選單';

    public function handle()
    {
        $parentId = (int) $this->option('parent');
        $force = $this->option('force');

        $existing = AdminMenu::where('url_name', 'admin.orders.index')->first();

        if ($existing && !$force) {
            $this->warn("⚠️  「訂單管理」選單已存在（ID: {$existing->id}）");
            $this->info("   如需重新寫入，請加上 --force 參數");
            return 0;
        }

        if ($existing && $force) {
            $this->deleteExistingMenus();
            $this->info('🗑️  已刪除舊的選單資料');
        }

        $parent = AdminMenu::find($parentId);
        if (!$parent) {
            $this->error("❌ 找不到父層選單 ID: {$parentId}");
            AdminMenu::where('parent_id', 0)->where('type', 1)->get()->each(function ($menu) {
                $this->line("   [{$menu->id}] {$menu->title}");
            });
            return 1;
        }

        $level = $parent->level + 1;
        $this->info("📍 將新增到：{$parent->title}（ID: {$parent->id}）底下");
        $this->newLine();

        DB::beginTransaction();

        try {
            $nextId = AdminMenu::max('id') + 1;

            $menuItems = [
                [
                    'id' => $nextId,
                    'title' => '訂單管理',
                    'parent_id' => $parentId,
                    'type' => 1,
                    'level' => $level,
                    'url' => 'admin/orders',
                    'url_name' => 'admin.orders.index',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 20,
                ],
                [
                    'id' => $nextId + 1,
                    'title' => '訂單詳情',
                    'parent_id' => $nextId,
                    'type' => 0,
                    'level' => $level + 1,
                    'url' => 'admin/orders/show',
                    'url_name' => 'admin.orders.show',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 1,
                ],
            ];

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
                $this->line("   [{$item['id']}] {$item['title']} ({$typeLabel}) - {$item['url_name']}");
            }

            $this->newLine();
            $this->info('💡 提示：請執行 php artisan order:grant-permissions 授予權限');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ 新增失敗：' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function deleteExistingMenus()
    {
        $mainMenu = AdminMenu::where('url_name', 'admin.orders.index')->first();
        if ($mainMenu) {
            AdminMenu::where('parent_id', $mainMenu->id)->delete();
            $mainMenu->delete();
        }
        AdminMenu::whereIn('url_name', [
            'admin.orders.index',
            'admin.orders.show',
        ])->delete();
    }
}
