<?php
require __DIR__ . '/../vendor/autoload.php';

try {
    $mpdf = new \Mpdf\Mpdf([
        'format'        => 'A4-L',
        'margin_left'   => 10,
        'margin_right'  => 10,
        'margin_top'    => 10,
        'margin_bottom' => 10,
        'fontDir'       => [__DIR__ . '/../storage/fonts'],
        'fontdata'      => [
            'notosanstc' => ['R' => 'NotoSansTC.ttf'],
        ],
        'default_font'  => 'notosanstc',
    ]);

    $html = '<p style="font-family:notosanstc; font-size:14px; color:#000;">Hello 你好 車廂 規格</p>';
    $mpdf->WriteHTML($html);
    $out = $mpdf->Output('', 'S');
    file_put_contents(__DIR__ . '/../storage/simple-test.pdf', $out);
    echo 'OK pages=1 size=' . strlen($out) . PHP_EOL;
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
