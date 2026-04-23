<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TaiwanLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 暫時關閉外鍵檢查
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // 清理資料表
        DB::table('list_area')->truncate();
        DB::table('list_city')->truncate();
        
        // 重新開啟外鍵檢查
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 從 JSON 檔案匯入資料
        $this->seedFromJson();
    }

    /**
     * 從 CityCountyData.json 匯入縣市區域資料
     */
    private function seedFromJson(): void
    {
        $jsonFile = database_path('data/CityCountyData.json');
        
        if (!File::exists($jsonFile)) {
            $this->command->error("JSON 檔案不存在: {$jsonFile}");
            return;
        }

        $jsonContent = File::get($jsonFile);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("JSON 解析錯誤: " . json_last_error_msg());
            return;
        }

        // 城市編號對應表（根據原始資料）
        $citySnMapping = [
            '臺北市' => 1,
            '基隆市' => 2,
            '新北市' => 3,
            '連江縣' => 25,  // 原本是4，改為25
            '宜蘭縣' => 5,
            '釣魚臺' => 6,   // 這個會被過濾掉
            '新竹市' => 7,
            '新竹縣' => 8,
            '桃園市' => 9,
            '苗栗縣' => 10,
            '臺中市' => 11,
            '彰化縣' => 12,
            '南投縣' => 13,
            '嘉義市' => 14,
            '嘉義縣' => 15,
            '雲林縣' => 16,
            '臺南市' => 17,
            '高雄市' => 18,
            '南海島' => 19,  // 東沙、南沙會合併到高雄市
            '澎湖縣' => 20,
            '金門縣' => 21,
            '屏東縣' => 22,
            '臺東縣' => 23,
            '花蓮縣' => 24,
        ];

        $areaSn = 1; // 區域編號計數器

        foreach ($data as $cityData) {
            $cityName = $cityData['CityName'];
            $cityEngName = $cityData['CityEngName'];
            
            // 過濾釣魚臺和南海島
            if ($cityName === '釣魚臺' || $cityName === '南海島') {
                continue;
            }

            if (!isset($citySnMapping[$cityName])) {
                $this->command->warn("未知的城市: {$cityName}");
                continue;
            }

            $citySn = $citySnMapping[$cityName];

            // 插入城市資料（JSON 格式）
            DB::table('list_city')->insert([
                'sn' => $citySn,
                'title' => json_encode([
                    'zh_TW' => $cityName,
                    'en' => $cityEngName
                ], JSON_UNESCAPED_UNICODE)
            ]);

            $this->command->info("已匯入城市: {$cityName} ({$cityEngName})");

            // 插入區域資料
            foreach ($cityData['AreaList'] as $areaData) {
                $areaName = $areaData['AreaName'];
                $areaEngName = $areaData['AreaEngName'];
                $zipCode = $areaData['ZipCode'];

                // 特殊處理：東沙群島和南沙群島屬於高雄市
                if ($areaName === '東沙群島' || $areaName === '南沙群島') {
                    $actualCitySn = 18; // 高雄市
                } else {
                    $actualCitySn = $citySn;
                }

                // 檢查是否已存在相同「郵遞區號+區域名稱」的區域（避免重複）
                // 注意：新竹市(300)、嘉義市(600)有多個區共用同一郵遞區號
                $exists = DB::table('list_area')
                    ->where('zipcode', $zipCode)
                    ->where('city_sn', $actualCitySn)
                    ->whereRaw("JSON_EXTRACT(title, '$.zh_TW') = ?", [$areaName])
                    ->exists();

                if (!$exists) {
                    DB::table('list_area')->insert([
                        'sn' => $areaSn++,
                        'city_sn' => $actualCitySn,
                        'title' => json_encode([
                            'zh_TW' => $areaName,
                            'en' => $areaEngName
                        ], JSON_UNESCAPED_UNICODE),
                        'zipcode' => $zipCode
                    ]);
                }
            }
        }

        // 統計結果
        $cityCount = DB::table('list_city')->count();
        $areaCount = DB::table('list_area')->count();
        
        $this->command->info("=========================================");
        $this->command->info("匯入完成！");
        $this->command->info("城市數量: {$cityCount}");
        $this->command->info("區域數量: {$areaCount}");
        $this->command->info("=========================================");
    }
}