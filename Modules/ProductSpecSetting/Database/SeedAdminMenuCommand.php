<?php

namespace Modules\ProductSpecSetting\Database;

use Illuminate\Console\Command;
use App\Models\AdminMenu;
use Illuminate\Support\Facades\DB;

/**
 * ProductSpecSetting 模組 - 選單 Seeder 指令
 * 
 * 使用方式：
 * php artisan product-spec:seed-admin-menu
 */
class SeedAdminMenuCommand extends Command
{
    protected $signature = 'product-spec:seed-admin-menu 
                            {--parent=62 : 父層選單 ID（預設: 62 其他管理系統）}
                            {--force : 強制重新寫入（會先刪除舊的再新增）}';

    protected $description = '新增「商品規格設定」到後台選單';

    public function handle()
    {
        $parentId = (int) $this->option('parent');
        $force = $this->option('force');

        // 檢查是否已存在
        $existing = AdminMenu::where('url_name', 'admin.product-spec-settings.index')
            ->orWhere('url_name', 'admin.product-spec-settings')
            ->first();

        if ($existing && !$force) {
            $this->warn("⚠️  「商品規格設定」選單已存在（ID: {$existing->id}）");
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
                // 主選單：商品規格設定
                [
                    'id' => $nextId,
                    'title' => '商品規格設定',
                    'parent_id' => $parentId,
                    'type' => 1,
                    'level' => $level,
                    'url' => 'admin/product-spec-settings',
                    'url_name' => 'admin.product-spec-settings.index',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 11,
                ],
                // 子選單（隱藏）：新增
                [
                    'id' => $nextId + 1,
                    'title' => '新增商品規格',
                    'parent_id' => $nextId,
                    'type' => 0,
                    'level' => $level + 1,
                    'url' => 'admin/product-spec-settings/groups/add',
                    'url_name' => 'admin.product-spec-settings.add',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 1,
                ],
                // 子選單（隱藏）：編輯
                [
                    'id' => $nextId + 2,
                    'title' => '編輯商品規格',
                    'parent_id' => $nextId,
                    'type' => 0,
                    'level' => $level + 1,
                    'url' => 'admin/product-spec-settings/groups/edit',
                    'url_name' => 'admin.product-spec-settings.edit',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 2,
                ],
                // 子選單（隱藏）：刪除
                [
                    'id' => $nextId + 3,
                    'title' => '刪除商品規格',
                    'parent_id' => $nextId,
                    'type' => 0,
                    'level' => $level + 1,
                    'url' => '',
                    'url_name' => 'admin.product-spec-settings.destroy',
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
            $this->info('💡 提示：如需同步權限，請執行 php artisan product-spec:grant-permissions');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ 新增失敗：' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 刪除已存在的商品規格設定相關選單
     */
    protected function deleteExistingMenus()
    {
        $urlNames = [
            'admin.product-spec-settings.index',
            'admin.product-spec-settings',
            'admin.product-spec-settings.add',
            'admin.product-spec-settings.edit',
            'admin.product-spec-settings.destroy',
        ];

        // 找到主選單
        $mainMenu = AdminMenu::where('url_name', 'admin.product-spec-settings.index')
            ->orWhere('url_name', 'admin.product-spec-settings')
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
