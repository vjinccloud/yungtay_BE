<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\AdminMenu;
use Illuminate\Support\Facades\DB;
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminMenu =  AdminMenu::all();
        $arr = [];
        foreach ($adminMenu as $k => $v) {
            $arr[] = [
                'id'=>$v->id,
                'name'=>$v->url_name,
                'guard_name' => 'admin',
            ];
        }
        // 禁用外键约束
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 清空表
        DB::table('permissions')->truncate();
        
        // 恢复外键约束
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        Permission::upsert(
            $arr, [], ['name','guard_name']
        );

    }
}
