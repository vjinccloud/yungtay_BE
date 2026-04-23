<?php

namespace Modules\NewsManagement\Database;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GrantPermissionsCommand extends Command
{
    protected $signature = 'news-management:grant-permissions';
    protected $description = '授予最新消息管理模組權限給管理員角色';

    public function handle(): void
    {
        $permissions = [
            'admin.news-management.index',
            'admin.news-management.create',
            'admin.news-management.edit',
            'admin.news-management.destroy',
        ];

        $role = Role::findByName('最高管理者', 'admin');

        $this->info("🔑 角色：{$role->name}");

        foreach ($permissions as $permName) {
            $perm = Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'admin',
            ]);

            if (!$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }

            $this->info("   ✅ 已授予：{$permName}");
        }

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('🧹 快取已清除');
        $this->info('🎉 完成！請重新整理後台頁面');
    }
}
