<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CnaCategoryMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = [
            // 政治類
            ['code' => 'PD', 'name' => '國內政治'],
            ['code' => 'PF', 'name' => '國外政治'],
            ['code' => 'PM', 'name' => '大陸政治'],
            ['code' => 'CD', 'name' => '國內國會'],
            // 財經類
            ['code' => 'ED', 'name' => '國內財經'],
            ['code' => 'EF', 'name' => '國外財經'],
            ['code' => 'EM', 'name' => '大陸財經'],
            // 文教類
            ['code' => 'DD', 'name' => '國內文教'],
            ['code' => 'DF', 'name' => '國外文教'],
            ['code' => 'DM', 'name' => '大陸文教'],
            // 科技類
            ['code' => 'HD', 'name' => '國內科技'],
            ['code' => 'HF', 'name' => '國外科技'],
            ['code' => 'HM', 'name' => '大陸科技'],
            // 社會類
            ['code' => 'JD', 'name' => '國內社會'],
            ['code' => 'JF', 'name' => '國外社會'],
            ['code' => 'JM', 'name' => '大陸社會'],
            // 體育類
            ['code' => 'LD', 'name' => '國內體育'],
            ['code' => 'LF', 'name' => '國外體育'],
            ['code' => 'LM', 'name' => '大陸體育'],
            // 醫藥衛生類
            ['code' => 'MD', 'name' => '國內醫藥衛生'],
            ['code' => 'MF', 'name' => '國外醫藥衛生'],
            ['code' => 'MM', 'name' => '大陸醫藥衛生'],
            // 交通類
            ['code' => 'TD', 'name' => '國內交通'],
            ['code' => 'TF', 'name' => '國外交通'],
            ['code' => 'TM', 'name' => '大陸交通'],
            // 兩岸類
            ['code' => 'PP', 'name' => '兩岸要聞'],
            // 其他
            ['code' => 'LG', 'name' => '地方新聞'],
            ['code' => 'VD', 'name' => '國內影劇'],
            ['code' => 'VF', 'name' => '國外影劇'],
            ['code' => 'VM', 'name' => '大陸影劇'],
            ['code' => 'WE', 'name' => '氣象'],
            ['code' => 'XX', 'name' => '編輯公電'],
        ];

        // 取得新聞分類（article）的最大 seq 值
        $maxSeq = Category::where('type', Category::TYPE_ARTICLE)
            ->whereNull('parent_id')
            ->max('seq') ?? 0;

        foreach ($mappings as $index => $mapping) {
            // 生成 slug（使用分類代碼和名稱）
            $slug = Str::slug($mapping['code'] . '-' . $mapping['name']);
            
            // 更新現有分類或建立新分類
            Category::updateOrCreate(
                [
                    'source_code' => $mapping['code'],
                    'source_provider' => 'cna',
                    'type' => Category::TYPE_ARTICLE
                ],
                [
                    'name' => ['zh_TW' => $mapping['name'], 'en' => $mapping['name']],
                    'slug' => $slug,
                    'status' => 1,
                    'seq' => $maxSeq + $index + 1,  // 自動遞增序號
                    'parent_id' => null,  // 確保是主分類
                    'level' => 1,
                ]
            );
        }
    }
}
