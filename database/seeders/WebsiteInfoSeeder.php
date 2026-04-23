<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WebsiteInfo;

class WebsiteInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        WebsiteInfo::upsert([
            [
                'id' => 1,
                'title' => json_encode([
                    'zh_TW' => '信吉衛視',
                    'en' => 'SJTV'
                ]),
                'tel' => '05-3701199',
                'fax' => '05-3660026',
                'email' => 'service@sjtv.com.tw'
            ]
        ], ['id'], [
            'title',
            'tel',
            'fax',
            'email'
        ]);
    }
}
