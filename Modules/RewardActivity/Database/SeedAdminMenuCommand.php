<?php

namespace Modules\RewardActivity\Database;

use App\Models\AdminMenu;
use Illuminate\Console\Command;

class SeedAdminMenuCommand extends Command
{
    protected $signature = 'reward-activity:seed-admin-menu
                            {--parent=62 : 上層選單 ID}
                            {--force : 刪除舊選單後重新建立}';

    protected $description = '建立回饋活動的後台選單';

    public function handle()
    {
        $parentId = (int) $this->option('parent');
        $force    = $this->option('force');

        $maxId   = AdminMenu::max('id') ?? 0;
        $startId = max($maxId + 1, 200);

        $existing = AdminMenu::where('url_name', 'admin.reward-activities.index')->first();
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

        $this->info('🎉 回饋活動選單建立完成！');
        return 0;
    }

    protected function deleteExistingMenus()
    {
        $urlNames = [
            'admin.reward-activities.index',
            'admin.reward-activities.create',
            'admin.reward-activities.edit',
            'admin.reward-activities.destroy',
        ];

        $deleted = AdminMenu::whereIn('url_name', $urlNames)->delete();
        if ($deleted) {
            $this->warn("🗑️  已刪除 {$deleted} 筆舊選單");
        }
    }
}
