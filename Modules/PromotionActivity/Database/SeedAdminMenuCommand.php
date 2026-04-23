<?php

namespace Modules\PromotionActivity\Database;

use App\Models\AdminMenu;
use Illuminate\Console\Command;

class SeedAdminMenuCommand extends Command
{
    protected $signature = 'promotion-activity:seed-admin-menu
                            {--parent=62 : 上層選單 ID}
                            {--force : 刪除舊選單後重新建立}';

    protected $description = '建立滿額免運設定的後台選單';

    public function handle()
    {
        $parentId = (int) $this->option('parent');
        $force    = $this->option('force');

        // 找可用的起始 ID
        $maxId   = AdminMenu::max('id') ?? 0;
        $startId = max($maxId + 1, 180);

        // 檢查是否已存在
        $existing = AdminMenu::where('url_name', 'admin.promotion-activity')->first();
        if ($existing && !$force) {
            $this->warn("⚠️  選單已存在（ID: {$existing->id}）。使用 --force 重新建立。");
            return 0;
        }

        if ($force) {
            $this->deleteExistingMenus();
        }

        $menuItems = MenuData::getData($startId, $parentId);

        foreach ($menuItems as $item) {
            AdminMenu::upsert([$item], ['id'], array_keys($item));
            $this->info("✅ 選單：{$item['title']}（ID: {$item['id']}）");
        }

        $this->info('🎉 滿額免運設定選單建立完成！');
        return 0;
    }

    protected function deleteExistingMenus()
    {
        $urlNames = [
            'admin.promotion-activities.index',
            'admin.promotion-activities.create',
            'admin.promotion-activities.edit',
            'admin.promotion-activities.destroy',
            'admin.promotion-activity',
        ];

        $deleted = AdminMenu::whereIn('url_name', $urlNames)->delete();
        if ($deleted) {
            $this->warn("🗑️  已刪除 {$deleted} 筆舊選單");
        }
    }
}
