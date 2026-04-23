<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 綠界支付設定 (ECPay Payment)
    |--------------------------------------------------------------------------
    | 站內付款相關設定
    */
    'payment' => [
        'MERCHANT_ID'       => env('ECPAY_PAYMENT_MERCHANT_ID', '3002607'),
        'HASH_KEY'          => env('ECPAY_PAYMENT_HASH_KEY', 'pwFHCqoQZGmho4w6'),
        'HASH_IV'           => env('ECPAY_PAYMENT_HASH_IV', 'EkRm7iFT261dpevs'),
        'API_URL'           => env('ECPAY_PAYMENT_API_URL', 'https://ecpg-stage.ecpay.com.tw'),
        'RETURN_URL'        => env('ECPAY_PAYMENT_RETURN_URL', 'https://honchuan.vjinc.biz/api/ecpay/payment/notify'),
        'ORDER_RESULT_URL'  => env('ECPAY_PAYMENT_ORDER_RESULT_URL', 'https://liff.line.me/2008810300-XzWZDglR/register'),
        'SEARCH_ORDER_URL'  => env('ECPAY_PAYMENT_SEARCH_ORDER_URL', 'https://ecpayment.ecpay.com.tw/1.0.0/Cashier/QueryTrade'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 綠界電子發票設定 (ECPay Invoice)
    |--------------------------------------------------------------------------
    | B2C 電子發票相關設定
    */
    'invoice' => [
        'MERCHANT_ID' => env('ECPAY_INVOICE_MERCHANT_ID', '2000132'),
        'HASH_KEY'    => env('ECPAY_INVOICE_HASH_KEY', 'ejCk326UnaZWKisg'),
        'HASH_IV'     => env('ECPAY_INVOICE_HASH_IV', 'q9jcZX8Ib9LM8wYk'),
        'API_URL'     => env('ECPAY_INVOICE_API_URL', 'https://einvoice-stage.ecpay.com.tw'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 綠界物流設定 (ECPay Logistics C2C)
    |--------------------------------------------------------------------------
    | 超商取貨相關設定
    */
    'logistics' => [
        'MERCHANT_ID' => env('ECPAY_LOGISTICS_MERCHANT_ID', '2000933'),
        'HASH_KEY'    => env('ECPAY_LOGISTICS_HASH_KEY', 'XBERn1YOvpM9nfZc'),
        'HASH_IV'     => env('ECPAY_LOGISTICS_HASH_IV', 'h1ONHk4P4yqbl5LK'),
        'API_URL'     => env('ECPAY_LOGISTICS_API_URL', 'https://logistics-stage.ecpay.com.tw'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 測試模式
    |--------------------------------------------------------------------------
    */
    'sandbox' => env('ECPAY_SANDBOX', true),
];
