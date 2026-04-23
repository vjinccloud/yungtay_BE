<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\AdminUser;

class AssignRolesSeeder  extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::find(1);
        $admin = AdminUser::where('email','admin@gmail.com')->get()->first();
        $admin->syncRoles([]);
        $admin->assignRole($role);
    }
}
