<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$order = \Modules\HistoryOrder\Backend\Model\HistoryOrder::first();
$html  = view('pdf.history-order', [
    'order'               => $order,
    'cabinSpecFields'     => \Modules\HistoryOrder\Backend\Model\HistoryOrder::getCabinSpecFields(),
    'entranceSpecFields'  => \Modules\HistoryOrder\Backend\Model\HistoryOrder::getEntranceSpecFields(),
    'elevatorImageBase64' => '',
    'iconBase64Map'       => [],
])->render();

file_put_contents(__DIR__ . '/../storage/pdf-debug.html', $html);
echo 'OK len=' . strlen($html) . PHP_EOL;
echo substr($html, 0, 300) . PHP_EOL;
