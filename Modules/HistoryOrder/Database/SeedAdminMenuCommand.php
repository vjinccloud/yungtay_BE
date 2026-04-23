<?php

namespace Modules\HistoryOrder\Database;

use Illuminate\Console\Command;
use App\Models\AdminMenu;
use Illuminate\Support\Facades\DB;

/**
 * HistoryOrder 模組 - 選單 Seeder 指令
 * 
 * 使用方式：
 * php artisan history-order:seed-admin-menu
 */
class SeedAdminMenuCommand extends Command
{
    protected $signature = 'history-order:seed-admin-menu 
                            {--parent= : 父層選單 ID}
                            {--force : 強制重新寫入}';

    protected $description = '新增「歷史訂單」到後台選單';

    public function handle()
    {
        $parentId = (int) $this->option('parent');
        $force = $this->option('force');

        if (!$parentId) {
            $this->info('📋 請選擇父層選單：');
            AdminMenu::where('parent_id', 0)->where('type', 1)->get()->each(function ($menu) {
                $this->line("   [{$menu->id}] {$menu->title}");
            });
            $parentId = (int) $this->ask('請輸入父層選單 ID');
        }

        $existing = AdminMenu::where('url_name', 'admin.history-order.index')->first();

        if ($existing && !$force) {
            $this->warn("⚠️  「歷史訂單」選單已存在（ID: {$existing->id}）");
            $this->info("   如需重新寫入，請加上 --force 參數");
            return 0;
        }

        if ($existing && $force) {
            AdminMenu::where('url_name', 'like', 'admin.history-order.%')->delete();
            $this->info('🗑️  已刪除舊的選單資料');
        }

        $parent = AdminMenu::find($parentId);
        if (!$parent) {
            $this->error("❌ 找不到父層選單 ID: {$parentId}");
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
                    'title' => '歷史訂單',
                    'parent_id' => $parentId,
                    'type' => 1,
                    'level' => $level,
                    'url' => 'admin/history-order',
                    'url_name' => 'admin.history-order.index',
                    'icon_image' => '',
                    'status' => 1,
                    'seq' => 30,
                ],
                [
                    'id' => $nextId + 1,
                    'title' => '歷史訂單詳情',
                    'parent_id' => $nextId,
                    'type' => 0,
                    'level' => $level + 1,
                    'url' => 'admin/history-order/show',
                    'url_name' => 'admin.history-order.show',
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
            $this->info('💡 提示：如需授予權限，請執行: php artisan history-order:grant-permissions');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('❌ 寫入失敗: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
