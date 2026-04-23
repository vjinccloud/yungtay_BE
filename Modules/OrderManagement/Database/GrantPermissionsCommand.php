<?php

namespace Modules\OrderManagement\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 授予角色訂單管理權限
 * 
 * php artisan order:grant-permissions
 */
class GrantPermissionsCommand extends Command
{
    protected $signature = 'order:grant-permissions
                            {--role= : 指定角色名稱}';

    protected $description = '授予角色「訂單管理」相關權限';

    public function handle()
    {
        $permissions = [
            'admin.orders.index',
            'admin.orders.show',
        ];

        foreach ($permissions as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'admin',
            ]);
        }

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
            $role->givePermissionTo($permissions);
            $this->info("✅ 角色「{$role->name}」已授予訂單管理權限");
        }

        Cache::flush();
        $this->info('🔄 已清除快取');

        return 0;
    }
}
