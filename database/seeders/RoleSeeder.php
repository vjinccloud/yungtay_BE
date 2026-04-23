<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Role::upsert([
            [
                'id'      =>  '1',
                'name'      =>  '最高管理者',
                'description' => '最大權限',
                'guard_name'  =>  'admin',
            ]
        ], ['id'], ['name','description','guard_name']);
    }
}
