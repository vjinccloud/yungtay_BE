<?php

namespace Database\Seeders;

use App\Models\ProductService;
use Illuminate\Database\Seeder;

class ProductServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => ['zh_TW' => '爪蓋', 'en' => 'Claw Cap'], 'sort' => 1],
            ['name' => ['zh_TW' => '鋁蓋', 'en' => 'Aluminum Cap'], 'sort' => 2],
            ['name' => ['zh_TW' => '塑蓋', 'en' => 'Plastic Cap'], 'sort' => 3],
            ['name' => ['zh_TW' => '標籤', 'en' => 'Label'], 'sort' => 4],
            ['name' => ['zh_TW' => 'PET寶特瓶', 'en' => 'PET Bottle'], 'sort' => 5],
            ['name' => ['zh_TW' => '瓶胚', 'en' => 'Preform'], 'sort' => 6],
            ['name' => ['zh_TW' => '無菌充填', 'en' => 'Aseptic Filling'], 'sort' => 7],
            ['name' => ['zh_TW' => '熱充填', 'en' => 'Hot Filling'], 'sort' => 8],
            ['name' => ['zh_TW' => '水充填', 'en' => 'Water Filling'], 'sort' => 9],
            ['name' => ['zh_TW' => '冷高壓充填', 'en' => 'Cold High Pressure Filling'], 'sort' => 10],
        ];

        foreach ($services as $service) {
            ProductService::updateOrCreate(
                ['name->zh_TW' => $service['name']['zh_TW']],
                array_merge($service, ['is_enabled' => true])
            );
        }
    }
}
