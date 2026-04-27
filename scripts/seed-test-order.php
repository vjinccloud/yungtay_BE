<?php
// 插入一筆有圖片的測試訂單（用 icon_01.png 充當電梯渲染圖）
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Modules\HistoryOrder\Backend\Model\HistoryOrder;
use Illuminate\Support\Facades\DB;

// 建立或更新第一筆測試資料
$order = HistoryOrder::updateOrCreate(
    ['customer_name' => 'xx股份有限公司'],
    [
        'order_name'             => '日立永大_xx股份有限公司',
        'series_model'           => 'EAS-II',
        'project_name'           => '世界明珠',
        'construction_location'  => '台北市大安區xxcccc123號',
        'customer_contact_name'  => '陳委克',
        'customer_contact_email' => 'mcchen@gmail.com',
        'sales_name'             => '林東方',
        'sales_email'            => 'linett@gmail.com',
        'sales_phone'            => '02-2709-3355#562',
        'note'                   => '請於交屋前確認色樣',
        // 用 icon_01.png 充當渲染圖（只是測試用）
        'elevator_image'         => '/images/icon_01.png',
        'cabin_specs'            => json_encode([
            'ceiling'       => "CH5\n髮紋不銹鋼\n燈光：黃",
            'door_panel'    => '彩繪鋼板',
            'side_panel'    => "彩繪鋼板\xE3\x80\x80前側板 (中間片)\n彩繪鋼板\xE3\x80\x80前側板 (兩側片)\n彩繪鋼板\xE3\x80\x80後側板 (中間片)\n彩繪鋼板\xE3\x80\x80後側板 (兩側片)",
            'floor'         => '大理石',
            'control_panel' => "BL-C3\xE3\x80\x80車廂操作盤\n無\xE3\x80\x80無障礙操作盤",
            'handrail'      => 'NR-200',
            'trim'          => '鏡面不銹鋼',
        ], JSON_UNESCAPED_UNICODE),
        'entrance_specs' => json_encode([
            'door_panel'    => 'NR-108',
            'door_frame'    => "標準門框\n髮紋不銹鋼",
            'door_column'   => '不銹鋼',
            'floor'         => '無',
            'control_panel' => "BL-C2\xE3\x80\x80乘場操作盤\nHF-LM5(LED)\xE3\x80\x80乘場指示器",
        ], JSON_UNESCAPED_UNICODE),
    ]
);

echo "OK: id={$order->id}, elevator_image={$order->elevator_image}" . PHP_EOL;
echo "cabin_specs  = " . $order->cabin_specs . PHP_EOL;
echo "entrance_specs = " . $order->entrance_specs . PHP_EOL;
