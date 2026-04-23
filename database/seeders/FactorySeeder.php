<?php

namespace Database\Seeders;

use App\Models\Factory;
use App\Models\Region;
use Illuminate\Database\Seeder;

class FactorySeeder extends Seeder
{
    public function run(): void
    {
        $factories = [
            // 台灣 (10間)
            ['region' => '台灣', 'name' => ['zh_TW' => '台北廠', 'en' => 'Taipei Factory'], 'address' => ['zh_TW' => '台北市內湖區瑞光路100號', 'en' => 'No. 100, Ruiguang Rd., Neihu Dist., Taipei'], 'contact_person' => '王大明', 'sort' => 1],
            ['region' => '台灣', 'name' => ['zh_TW' => '桃園廠', 'en' => 'Taoyuan Factory'], 'address' => ['zh_TW' => '桃園市蘆竹區南崁路200號', 'en' => 'No. 200, Nankan Rd., Luzhu Dist., Taoyuan'], 'contact_person' => '李小華', 'sort' => 2],
            ['region' => '台灣', 'name' => ['zh_TW' => '新竹廠', 'en' => 'Hsinchu Factory'], 'address' => ['zh_TW' => '新竹縣湖口鄉工業路50號', 'en' => 'No. 50, Industrial Rd., Hukou Township, Hsinchu'], 'contact_person' => '張志明', 'sort' => 3],
            ['region' => '台灣', 'name' => ['zh_TW' => '台中廠', 'en' => 'Taichung Factory'], 'address' => ['zh_TW' => '台中市西屯區工業區路88號', 'en' => 'No. 88, Industrial Zone Rd., Xitun Dist., Taichung'], 'contact_person' => '陳美玲', 'sort' => 4],
            ['region' => '台灣', 'name' => ['zh_TW' => '彰化廠', 'en' => 'Changhua Factory'], 'address' => ['zh_TW' => '彰化縣鹿港鎮工業路168號', 'en' => 'No. 168, Industrial Rd., Lukang Township, Changhua'], 'contact_person' => '林建宏', 'sort' => 5],
            ['region' => '台灣', 'name' => ['zh_TW' => '嘉義廠', 'en' => 'Chiayi Factory'], 'address' => ['zh_TW' => '嘉義縣民雄鄉工業路66號', 'en' => 'No. 66, Industrial Rd., Minxiong Township, Chiayi'], 'contact_person' => '黃淑芬', 'sort' => 6],
            ['region' => '台灣', 'name' => ['zh_TW' => '台南廠', 'en' => 'Tainan Factory'], 'address' => ['zh_TW' => '台南市永康區中正路500號', 'en' => 'No. 500, Zhongzheng Rd., Yongkang Dist., Tainan'], 'contact_person' => '吳宗翰', 'sort' => 7],
            ['region' => '台灣', 'name' => ['zh_TW' => '高雄廠', 'en' => 'Kaohsiung Factory'], 'address' => ['zh_TW' => '高雄市楠梓區加工出口區路1號', 'en' => 'No. 1, Export Processing Zone Rd., Nanzi Dist., Kaohsiung'], 'contact_person' => '蔡雅婷', 'sort' => 8],
            ['region' => '台灣', 'name' => ['zh_TW' => '屏東廠', 'en' => 'Pingtung Factory'], 'address' => ['zh_TW' => '屏東縣屏東市工業路77號', 'en' => 'No. 77, Industrial Rd., Pingtung City, Pingtung'], 'contact_person' => '許家豪', 'sort' => 9],
            ['region' => '台灣', 'name' => ['zh_TW' => '宜蘭廠', 'en' => 'Yilan Factory'], 'address' => ['zh_TW' => '宜蘭縣五結鄉工業路33號', 'en' => 'No. 33, Industrial Rd., Wujie Township, Yilan'], 'contact_person' => '鄭佳琳', 'sort' => 10],

            // 中國大陸 (14間)
            ['region' => '中國大陸', 'name' => ['zh_TW' => '上海廠', 'en' => 'Shanghai Factory'], 'address' => ['zh_TW' => '上海市浦東新區工業路100號', 'en' => 'No. 100, Industrial Rd., Pudong, Shanghai'], 'contact_person' => '趙偉', 'sort' => 1],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '北京廠', 'en' => 'Beijing Factory'], 'address' => ['zh_TW' => '北京市大興區工業園路50號', 'en' => 'No. 50, Industrial Park Rd., Daxing, Beijing'], 'contact_person' => '孫麗', 'sort' => 2],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '廣州廠', 'en' => 'Guangzhou Factory'], 'address' => ['zh_TW' => '廣州市白雲區工業大道200號', 'en' => 'No. 200, Industrial Ave., Baiyun, Guangzhou'], 'contact_person' => '周強', 'sort' => 3],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '深圳廠', 'en' => 'Shenzhen Factory'], 'address' => ['zh_TW' => '深圳市寶安區工業路88號', 'en' => 'No. 88, Industrial Rd., Baoan, Shenzhen'], 'contact_person' => '吳芳', 'sort' => 4],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '蘇州廠', 'en' => 'Suzhou Factory'], 'address' => ['zh_TW' => '蘇州市工業園區金雞湖路66號', 'en' => 'No. 66, Jinji Lake Rd., Industrial Park, Suzhou'], 'contact_person' => '鄭明', 'sort' => 5],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '杭州廠', 'en' => 'Hangzhou Factory'], 'address' => ['zh_TW' => '杭州市蕭山區工業路168號', 'en' => 'No. 168, Industrial Rd., Xiaoshan, Hangzhou'], 'contact_person' => '王秀英', 'sort' => 6],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '南京廠', 'en' => 'Nanjing Factory'], 'address' => ['zh_TW' => '南京市江寧區開發區路100號', 'en' => 'No. 100, Development Zone Rd., Jiangning, Nanjing'], 'contact_person' => '李剛', 'sort' => 7],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '武漢廠', 'en' => 'Wuhan Factory'], 'address' => ['zh_TW' => '武漢市東西湖區工業路55號', 'en' => 'No. 55, Industrial Rd., Dongxihu, Wuhan'], 'contact_person' => '張華', 'sort' => 8],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '成都廠', 'en' => 'Chengdu Factory'], 'address' => ['zh_TW' => '成都市雙流區工業園路77號', 'en' => 'No. 77, Industrial Park Rd., Shuangliu, Chengdu'], 'contact_person' => '劉洋', 'sort' => 9],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '重慶廠', 'en' => 'Chongqing Factory'], 'address' => ['zh_TW' => '重慶市渝北區工業路99號', 'en' => 'No. 99, Industrial Rd., Yubei, Chongqing'], 'contact_person' => '陳軍', 'sort' => 10],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '天津廠', 'en' => 'Tianjin Factory'], 'address' => ['zh_TW' => '天津市濱海新區開發區路80號', 'en' => 'No. 80, Development Zone Rd., Binhai, Tianjin'], 'contact_person' => '楊敏', 'sort' => 11],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '青島廠', 'en' => 'Qingdao Factory'], 'address' => ['zh_TW' => '青島市黃島區工業路120號', 'en' => 'No. 120, Industrial Rd., Huangdao, Qingdao'], 'contact_person' => '黃海', 'sort' => 12],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '廈門廠', 'en' => 'Xiamen Factory'], 'address' => ['zh_TW' => '廈門市海滄區工業園路60號', 'en' => 'No. 60, Industrial Park Rd., Haicang, Xiamen'], 'contact_person' => '林志遠', 'sort' => 13],
            ['region' => '中國大陸', 'name' => ['zh_TW' => '東莞廠', 'en' => 'Dongguan Factory'], 'address' => ['zh_TW' => '東莞市長安鎮工業路150號', 'en' => "No. 150, Industrial Rd., Chang'an, Dongguan"], 'contact_person' => '朱曉燕', 'sort' => 14],

            // 印尼 (7間)
            ['region' => '印尼', 'name' => ['zh_TW' => '雅加達廠', 'en' => 'Jakarta Factory'], 'address' => ['zh_TW' => '雅加達工業區路100號', 'en' => 'No. 100, Industrial Zone Rd., Jakarta'], 'contact_person' => 'Budi Santoso', 'sort' => 1],
            ['region' => '印尼', 'name' => ['zh_TW' => '泗水廠', 'en' => 'Surabaya Factory'], 'address' => ['zh_TW' => '泗水工業園區路50號', 'en' => 'No. 50, Industrial Park Rd., Surabaya'], 'contact_person' => 'Dewi Kusuma', 'sort' => 2],
            ['region' => '印尼', 'name' => ['zh_TW' => '萬隆廠', 'en' => 'Bandung Factory'], 'address' => ['zh_TW' => '萬隆工業路88號', 'en' => 'No. 88, Industrial Rd., Bandung'], 'contact_person' => 'Andi Wijaya', 'sort' => 3],
            ['region' => '印尼', 'name' => ['zh_TW' => '棉蘭廠', 'en' => 'Medan Factory'], 'address' => ['zh_TW' => '棉蘭工業區路66號', 'en' => 'No. 66, Industrial Zone Rd., Medan'], 'contact_person' => 'Siti Rahayu', 'sort' => 4],
            ['region' => '印尼', 'name' => ['zh_TW' => '三寶壟廠', 'en' => 'Semarang Factory'], 'address' => ['zh_TW' => '三寶壟工業路77號', 'en' => 'No. 77, Industrial Rd., Semarang'], 'contact_person' => 'Hendra Gunawan', 'sort' => 5],
            ['region' => '印尼', 'name' => ['zh_TW' => '日惹廠', 'en' => 'Yogyakarta Factory'], 'address' => ['zh_TW' => '日惹工業園路33號', 'en' => 'No. 33, Industrial Park Rd., Yogyakarta'], 'contact_person' => 'Ratna Sari', 'sort' => 6],
            ['region' => '印尼', 'name' => ['zh_TW' => '巴厘島廠', 'en' => 'Bali Factory'], 'address' => ['zh_TW' => '巴厘島工業區路25號', 'en' => 'No. 25, Industrial Zone Rd., Bali'], 'contact_person' => 'Made Suryadi', 'sort' => 7],

            // 越南 (5間)
            ['region' => '越南', 'name' => ['zh_TW' => '胡志明市廠', 'en' => 'Ho Chi Minh City Factory'], 'address' => ['zh_TW' => '胡志明市工業區路100號', 'en' => 'No. 100, Industrial Zone Rd., Ho Chi Minh City'], 'contact_person' => 'Nguyen Van Minh', 'sort' => 1],
            ['region' => '越南', 'name' => ['zh_TW' => '河內廠', 'en' => 'Hanoi Factory'], 'address' => ['zh_TW' => '河內工業園區路50號', 'en' => 'No. 50, Industrial Park Rd., Hanoi'], 'contact_person' => 'Tran Thi Lan', 'sort' => 2],
            ['region' => '越南', 'name' => ['zh_TW' => '峴港廠', 'en' => 'Da Nang Factory'], 'address' => ['zh_TW' => '峴港工業路88號', 'en' => 'No. 88, Industrial Rd., Da Nang'], 'contact_person' => 'Le Van Hung', 'sort' => 3],
            ['region' => '越南', 'name' => ['zh_TW' => '海防廠', 'en' => 'Hai Phong Factory'], 'address' => ['zh_TW' => '海防工業區路66號', 'en' => 'No. 66, Industrial Zone Rd., Hai Phong'], 'contact_person' => 'Pham Thi Hoa', 'sort' => 4],
            ['region' => '越南', 'name' => ['zh_TW' => '芹苴廠', 'en' => 'Can Tho Factory'], 'address' => ['zh_TW' => '芹苴工業路55號', 'en' => 'No. 55, Industrial Rd., Can Tho'], 'contact_person' => 'Vo Van Thanh', 'sort' => 5],

            // 泰國 (5間)
            ['region' => '泰國', 'name' => ['zh_TW' => '曼谷廠', 'en' => 'Bangkok Factory'], 'address' => ['zh_TW' => '曼谷工業區路100號', 'en' => 'No. 100, Industrial Zone Rd., Bangkok'], 'contact_person' => 'Somchai Prasert', 'sort' => 1],
            ['region' => '泰國', 'name' => ['zh_TW' => '清邁廠', 'en' => 'Chiang Mai Factory'], 'address' => ['zh_TW' => '清邁工業園區路50號', 'en' => 'No. 50, Industrial Park Rd., Chiang Mai'], 'contact_person' => 'Supaluck Chaiyasit', 'sort' => 2],
            ['region' => '泰國', 'name' => ['zh_TW' => '春武里廠', 'en' => 'Chonburi Factory'], 'address' => ['zh_TW' => '春武里工業路88號', 'en' => 'No. 88, Industrial Rd., Chonburi'], 'contact_person' => 'Pattana Srisuk', 'sort' => 3],
            ['region' => '泰國', 'name' => ['zh_TW' => '羅勇廠', 'en' => 'Rayong Factory'], 'address' => ['zh_TW' => '羅勇工業區路66號', 'en' => 'No. 66, Industrial Zone Rd., Rayong'], 'contact_person' => 'Narong Kittirat', 'sort' => 4],
            ['region' => '泰國', 'name' => ['zh_TW' => '普吉廠', 'en' => 'Phuket Factory'], 'address' => ['zh_TW' => '普吉工業路55號', 'en' => 'No. 55, Industrial Rd., Phuket'], 'contact_person' => 'Apinya Wongchai', 'sort' => 5],

            // 馬來西亞 (2間)
            ['region' => '馬來西亞', 'name' => ['zh_TW' => '吉隆坡廠', 'en' => 'Kuala Lumpur Factory'], 'address' => ['zh_TW' => '吉隆坡工業區路100號', 'en' => 'No. 100, Industrial Zone Rd., Kuala Lumpur'], 'contact_person' => 'Ahmad Razak', 'sort' => 1],
            ['region' => '馬來西亞', 'name' => ['zh_TW' => '檳城廠', 'en' => 'Penang Factory'], 'address' => ['zh_TW' => '檳城工業園區路50號', 'en' => 'No. 50, Industrial Park Rd., Penang'], 'contact_person' => 'Lee Wei Ming', 'sort' => 2],

            // 緬甸 (4間)
            ['region' => '緬甸', 'name' => ['zh_TW' => '仰光廠', 'en' => 'Yangon Factory'], 'address' => ['zh_TW' => '仰光工業區路100號', 'en' => 'No. 100, Industrial Zone Rd., Yangon'], 'contact_person' => 'Aung Kyaw', 'sort' => 1],
            ['region' => '緬甸', 'name' => ['zh_TW' => '曼德勒廠', 'en' => 'Mandalay Factory'], 'address' => ['zh_TW' => '曼德勒工業路50號', 'en' => 'No. 50, Industrial Rd., Mandalay'], 'contact_person' => 'Than Htay', 'sort' => 2],
            ['region' => '緬甸', 'name' => ['zh_TW' => '內比都廠', 'en' => 'Naypyidaw Factory'], 'address' => ['zh_TW' => '內比都工業園區路88號', 'en' => 'No. 88, Industrial Park Rd., Naypyidaw'], 'contact_person' => 'Mya Win', 'sort' => 3],
            ['region' => '緬甸', 'name' => ['zh_TW' => '毛淡棉廠', 'en' => 'Mawlamyine Factory'], 'address' => ['zh_TW' => '毛淡棉工業路66號', 'en' => 'No. 66, Industrial Rd., Mawlamyine'], 'contact_person' => 'Zaw Min', 'sort' => 4],

            // 柬埔寨 (1間)
            ['region' => '柬埔寨', 'name' => ['zh_TW' => '金邊廠', 'en' => 'Phnom Penh Factory'], 'address' => ['zh_TW' => '金邊工業區路100號', 'en' => 'No. 100, Industrial Zone Rd., Phnom Penh'], 'contact_person' => 'Sok Vannak', 'sort' => 1],

            // 莫三比克 (2間)
            ['region' => '莫三比克', 'name' => ['zh_TW' => '馬普托廠', 'en' => 'Maputo Factory'], 'address' => ['zh_TW' => '馬普托工業區路100號', 'en' => 'No. 100, Industrial Zone Rd., Maputo'], 'contact_person' => 'Carlos Machel', 'sort' => 1],
            ['region' => '莫三比克', 'name' => ['zh_TW' => '貝拉廠', 'en' => 'Beira Factory'], 'address' => ['zh_TW' => '貝拉工業路50號', 'en' => 'No. 50, Industrial Rd., Beira'], 'contact_person' => 'Maria Joaquim', 'sort' => 2],
        ];

        foreach ($factories as $data) {
            $region = Region::where('name->zh_TW', $data['region'])->first();
            if ($region) {
                Factory::updateOrCreate(
                    [
                        'region_id' => $region->id,
                        'name->zh_TW' => $data['name']['zh_TW']
                    ],
                    [
                        'region_id' => $region->id,
                        'name' => $data['name'],
                        'address' => $data['address'],
                        'contact_person' => $data['contact_person'],
                        'sort' => $data['sort'],
                        'is_enabled' => true,
                    ]
                );
            }
        }
    }
}
