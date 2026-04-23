<?php

namespace Modules\FrontMenuSetting\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 授予角色前台選單管理權限
 * 
 * php artisan front-menu:grant-permissions
 */
class GrantPermissionsCommand extends Command
{
    protected $signature = 'front-menu:grant-permissions
                            {--role= : 指定角色名稱（不指定則授予所有角色）}';

    protected $description = '授予角色「前台選單管理」相關權限';

    public function handle()
    {
        $permissions = [
            'admin.front-menu-settings.index',
            'admin.front-menu-settings.add',
            'admin.front-menu-settings.edit',
            'admin.front-menu-settings.destroy',
        ];

        // 確保權限存在（沒有的話先建立）
        foreach ($permissions as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'admin',
            ]);
        }

        // 取得角色
        $roleName = $this->option('role');
        if ($roleName) {
            $roles = \Spatie\Permission\Models\Role::where('name', $roleName)
                ->where('guard_name', 'admin')
                ->get();
        } else {
            $roles = \Spatie\Permission\Models\Role::where('guard_name', 'admin')->get();
        }

        if ($roles->isEmpty()) {
            $this->error('❌ 找不到任何角色');
            return 1;
        }

        foreach ($roles as $role) {
            $this->info("🔑 角色：{$role->name}");

            foreach ($permissions as $permName) {
                if (!$role->hasPermissionTo($permName)) {
                    $role->givePermissionTo($permName);
                    $this->line("   ✅ 已授予：{$permName}");
                } else {
                    $this->line("   ✔️  已擁有：{$permName}");
                }
            }
        }

        // 清除所有快取
        Cache::flush();
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newLine();
        $this->info('🧹 快取已清除');
        $this->info('🎉 完成！請重新整理後台頁面');

        return 0;
    }
}
