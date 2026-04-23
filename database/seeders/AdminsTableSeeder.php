<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\AdminUser;


class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        AdminUser::truncate();
        Schema::enableForeignKeyConstraints();
        AdminUser::upsert([
            [
                'name'      =>  'Admin',
                'email'     =>  'admin@gmail.com',
                'password'  =>  bcrypt('Aa!123456'),
            ]
        ], ['email'], ['name','password']);
    }
}
