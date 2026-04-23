<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class GrantPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = Permission::all()->pluck('name')->toArray();
        $role = Role::find(1);
        $role->syncPermissions([]);
        $role->syncPermissions($permissions);
    }
}
