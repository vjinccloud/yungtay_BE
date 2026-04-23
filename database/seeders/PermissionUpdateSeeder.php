<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionUpdateSeeder extends Seeder
{
    /**
     * 權限更新專用 Seeder
     * 用於更新權限系統，不包含初始化資料
     */
    public function run(): void
    {
        // 更新管理員選單與權限
        $this->call(AdminMenuSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(GrantPermissionsSeeder::class);
        $this->call(AssignRolesSeeder::class);
    }
}