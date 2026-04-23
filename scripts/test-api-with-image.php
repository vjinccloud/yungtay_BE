<?php
/**
 * 測試歷史訂單 API（含圖片上傳）
 * 執行: php scripts/test-api-with-image.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// 使用現有的圖片作為測試
$imagePath = base_path('public/uploads/banners/desktop/2025/202512/20251224/img_694c00471360f6.22864790_1766588487.png');

if (!file_exists($imagePath)) {
    echo "找不到測試圖片: {$imagePath}\n";
    exit(1);
}

$file = new \Illuminate\Http\UploadedFile(
    $imagePath,
    'elevator_test.png',
    'image/png',
    null,
    true // test mode
);

$request = \Illuminate\Http\Request::create('/api/v1/history-orders', 'POST', [
    'order_name'             => '日立永大_測試建設(含圖片)',
    'customer_name'          => '測試建設股份有限公司',
    'project_name'           => '信義豪邸A區',
    'construction_location'  => '台北市信義區松仁路100號',
    'customer_contact_name'  => '王大明',
    'customer_contact_email' => 'wang.dm@test-build.com.tw',
    'series_model'           => 'HIT-V',
    'sales_name'             => '李佳韻',
    'sales_email'            => 'lee.jy@hitachi-elevator.com.tw',
    'sales_phone'            => '04-2345-6789#201',
    'note'                   => '含電梯渲染圖測試',
    'cabin_specs' => [
        'ceiling'       => "CH5\n髮紋不銹鋼\n燈光：白",
        'door_panel'    => '鏡面不銹鋼',
        'side_panel'    => "不銹鋼板-鏡面不銹鋼　前側板 (中間片)\n不銹鋼板-鏡面不銹鋼　前側板 (兩側片)\n不銹鋼板-鏡面不銹鋼　後側板 (中間片)\n不銹鋼板-鏡面不銹鋼　後側板 (兩側片)",
        'floor'         => 'PVC',
        'control_panel' => "BL-C3\n無　　車廂操作盤\n　　　無障礙操作盤",
        'handrail'      => 'NR-108',
        'trim'          => '鏡面不銹鋼',
    ],
    'entrance_specs' => [
        'door_panel'    => 'NR-108',
        'door_frame'    => "窄型門框\n鏡面不銹鋼",
        'door_column'   => '硬質鋁合金',
        'floor'         => 'PVC',
        'control_panel' => 'BL-C3',
    ],
], [], ['elevator_image' => $file]);

$response = $kernel->handle($request);

$json = json_decode($response->getContent(), true);
echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
