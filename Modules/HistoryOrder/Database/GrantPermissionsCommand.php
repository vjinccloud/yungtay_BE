<?php

namespace Modules\HistoryOrder\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 授予角色歷史訂單權限
 * 
 * php artisan history-order:grant-permissions
 */
class GrantPermissionsCommand extends Command
{
    protected $signature = 'history-order:grant-permissions
                            {--role= : 指定角色名稱}';

    protected $description = '授予角色「歷史訂單」相關權限';

    public function handle()
    {
        $permissions = [
            'admin.history-order.index',
            'admin.history-order.show',
            'admin.history-order.export',
        ];

        foreach ($permissions as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'admin',
            ]);
        }

        $this->info('✅ 已建立以下權限：');
        foreach ($permissions as $p) {
            $this->line("   - {$p}");
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
            $this->info("✅ 角色「{$role->name}」已授予歷史訂單權限");
        }

        Cache::flush();
        $this->info('🔄 已清除快取');

        return 0;
    }
}
