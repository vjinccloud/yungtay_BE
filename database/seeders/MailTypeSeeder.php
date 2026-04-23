<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MailType;

class MailTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MailType::upsert([
            [
                'id' => 1,
                'name' => '客服中心',
                'description' => '客服相關問題、意見回饋、聯絡我們',
                'seq' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ], ['id'], ['name', 'description', 'seq', 'status', 'updated_at']);
    }
}