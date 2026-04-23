<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminsTableSeeder::class);
        $this->call(AdminMenuSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(GrantPermissionsSeeder::class);
        $this->call(AssignRolesSeeder::class);
        $this->call(WebsiteInfoSeeder::class);
        $this->call(TaiwanLocationSeeder::class);
        $this->call(CnaCategoryMappingSeeder::class);
        $this->call(MailTypeSeeder::class);
        $this->call(DatabaseCommentSeeder::class);
    }
}
