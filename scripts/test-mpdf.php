<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = \Modules\HistoryOrder\Backend\Model\HistoryOrder::first();

// 電梯圖轉 base64
$elevatorImageBase64 = '';
if ($order->elevator_image) {
    $path = public_path(ltrim($order->elevator_image, '/'));
    if (file_exists($path)) {
        $mime = mime_content_type($path);
        $elevatorImageBase64 = "data:{$mime};base64," . base64_encode(file_get_contents($path));
    }
}

// icons 轉 base64
$iconBase64Map = [];
$allIcons = array_unique(array_merge(
    array_column(array_values(\Modules\HistoryOrder\Backend\Model\HistoryOrder::getCabinSpecFields()), 'icon'),
    array_column(array_values(\Modules\HistoryOrder\Backend\Model\HistoryOrder::getEntranceSpecFields()), 'icon')
));
foreach (array_filter($allIcons) as $iconPath) {
    $fullPath = public_path(ltrim($iconPath, '/'));
    if (file_exists($fullPath)) {
        $mime = mime_content_type($fullPath);
        $iconBase64Map[$iconPath] = "data:{$mime};base64," . base64_encode(file_get_contents($fullPath));
    }
}

$html  = view('pdf.history-order', [
    'order'               => $order,
    'cabinSpecFields'     => \Modules\HistoryOrder\Backend\Model\HistoryOrder::getCabinSpecFields(),
    'entranceSpecFields'  => \Modules\HistoryOrder\Backend\Model\HistoryOrder::getEntranceSpecFields(),
    'elevatorImageBase64' => $elevatorImageBase64,
    'iconBase64Map'       => $iconBase64Map,
])->render();

// 移除 BOM
$html = preg_replace('/\xEF\xBB\xBF/', '', $html);

try {
    $mpdf = new \Mpdf\Mpdf([
        'format'        => 'A4-L',
        'margin_left'   => 8,
        'margin_right'  => 8,
        'margin_top'    => 8,
        'margin_bottom' => 8,
        'mode'          => 'utf-8',
        'fontDir'       => [__DIR__ . '/../storage/fonts'],
        'fontdata'      => [
            'notosanstc' => ['R' => 'NotoSansTC.ttf'],
        ],
        'default_font'  => 'notosanstc',
    ]);

    $mpdf->WriteHTML($html);
    $out = $mpdf->Output('', 'S');
    file_put_contents(__DIR__ . '/../storage/test-output.pdf', $out);
    echo 'PDF OK, size=' . strlen($out) . ' bytes' . PHP_EOL;
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
