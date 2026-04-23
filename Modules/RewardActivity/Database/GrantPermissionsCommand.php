<?php

namespace Modules\RewardActivity\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GrantPermissionsCommand extends Command
{
    protected $signature = 'reward-activity:grant-permissions
                            {--role= : 指定角色名稱（不指定則授予所有角色）}';
    protected $description = '授予角色「回饋活動」相關權限';

    public function handle()
    {
        $permissions = [
            'admin.reward-activities.index',
            'admin.reward-activities.create',
            'admin.reward-activities.edit',
            'admin.reward-activities.destroy',
        ];

        foreach ($permissions as $permName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'admin',
            ]);
        }

        $roleName = $this->option('role');
        if ($roleName) {
            $roles = \Spatie\Permission\Models\Role::where('name', $roleName)
                ->where('guard_name', 'admin')->get();
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

        Cache::flush();
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('🧹 快取已清除');
        $this->info('🎉 完成！請重新整理後台頁面');
        return 0;
    }
}
