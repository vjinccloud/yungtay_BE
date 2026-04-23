<?php

namespace Modules\NewsCategoryManagement\Database;

use App\Models\AdminMenu;
use Illuminate\Console\Command;

class SeedAdminMenuCommand extends Command
{
    protected $signature = 'news-category-management:seed-admin-menu
                            {--parent=62 : 上層選單 ID}
                            {--force : 刪除舊選單後重新建立}';

    protected $description = '建立最新消息分類管理模組的後台選單';

    public function handle(): int
    {
        $parentId = (int) $this->option('parent');
        $force    = $this->option('force');

        $maxId   = AdminMenu::max('id') ?? 0;
        $startId = max($maxId + 1, 230);

        $existing = AdminMenu::where('url_name', 'admin.news-categories')->first();
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

        $this->info('🎉 最新消息分類管理選單建立完成！');
        return 0;
    }

    protected function deleteExistingMenus(): void
    {
        $urlNames = [
            'admin.news-categories',
            'admin.news-categories.add',
            'admin.news-categories.edit',
            'admin.news-categories.delete',
        ];

        $deleted = AdminMenu::whereIn('url_name', $urlNames)->delete();
        if ($deleted) {
            $this->warn("🗑️  已刪除 {$deleted} 筆舊選單");
        }
    }
}
