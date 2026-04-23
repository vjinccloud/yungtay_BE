<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['name' => ['zh_TW' => '台灣', 'en' => 'Taiwan'], 'sort' => 1],
            ['name' => ['zh_TW' => '中國大陸', 'en' => 'China'], 'sort' => 2],
            ['name' => ['zh_TW' => '印尼', 'en' => 'Indonesia'], 'sort' => 3],
            ['name' => ['zh_TW' => '越南', 'en' => 'Vietnam'], 'sort' => 4],
            ['name' => ['zh_TW' => '泰國', 'en' => 'Thailand'], 'sort' => 5],
            ['name' => ['zh_TW' => '馬來西亞', 'en' => 'Malaysia'], 'sort' => 6],
            ['name' => ['zh_TW' => '緬甸', 'en' => 'Myanmar'], 'sort' => 7],
            ['name' => ['zh_TW' => '柬埔寨', 'en' => 'Cambodia'], 'sort' => 8],
            ['name' => ['zh_TW' => '莫三比克', 'en' => 'Mozambique'], 'sort' => 9],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['name->zh_TW' => $region['name']['zh_TW']],
                array_merge($region, ['is_enabled' => true])
            );
        }
    }
}
