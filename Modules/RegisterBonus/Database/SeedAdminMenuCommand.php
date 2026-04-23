<?php

namespace Modules\RegisterBonus\Database;

use App\Models\AdminMenu;
use Illuminate\Console\Command;

class SeedAdminMenuCommand extends Command
{
    protected $signature = 'register-bonus:seed-admin-menu
                            {--parent=62 : 上層選單 ID}
                            {--force : 刪除舊選單後重新建立}';

    protected $description = '建立註冊購物金的後台選單';

    public function handle()
    {
        $parentId = (int) $this->option('parent');
        $force    = $this->option('force');

        $maxId   = AdminMenu::max('id') ?? 0;
        $startId = max($maxId + 1, 190);

        $existing = AdminMenu::where('url_name', 'admin.register-bonus')->first();
        if ($existing && !$force) {
            $this->warn("⚠️  選單已存在（ID: {$existing->id}）。使用 --force 重新建立。");
            return 0;
        }

        if ($force) {
            $deleted = AdminMenu::where('url_name', 'admin.register-bonus')->delete();
            if ($deleted) {
                $this->warn("🗑️  已刪除 {$deleted} 筆舊選單");
            }
        }

        $menuItems = MenuData::getData($startId, $parentId);

        foreach ($menuItems as $item) {
            AdminMenu::upsert([$item], ['id'], array_keys($item));
            $this->info("✅ 選單：{$item['title']}（ID: {$item['id']}）");
        }

        $this->info('🎉 註冊購物金選單建立完成！');
        return 0;
    }
}
