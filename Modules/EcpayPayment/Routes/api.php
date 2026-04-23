<?php

use Illuminate\Support\Facades\Route;
use Modules\EcpayPayment\Backend\Controller\EcpayController;

/*
|--------------------------------------------------------------------------
| 綠界金流 API 路由
|--------------------------------------------------------------------------
*/

Route::prefix('ecpay')->name('ecpay.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 付款相關 API
    |--------------------------------------------------------------------------
    */
    Route::prefix('payment')->name('payment.')->group(function () {
        // 建立付款 Token
        Route::post('/create-token', [EcpayController::class, 'createPaymentToken'])
            ->name('create-token');

        // 執行付款
        Route::post('/execute', [EcpayController::class, 'executePayment'])
            ->name('execute');

        // 付款通知回調 (綠界背景通知)
        Route::post('/notify', [EcpayController::class, 'paymentNotify'])
            ->name('notify');

        // 付款結果導向
        Route::post('/result', [EcpayController::class, 'paymentResult'])
            ->name('result');

        // 查詢訂單付款狀態
        Route::get('/query/{merchantTradeNo}', [EcpayController::class, 'queryPayment'])
            ->name('query');
    });

    /*
    |--------------------------------------------------------------------------
    | 物流相關 API
    |--------------------------------------------------------------------------
    */
    Route::prefix('logistics')->name('logistics.')->group(function () {
        // 產生門市地圖表單
        Route::post('/map', [EcpayController::class, 'generateMapForm'])
            ->name('map');

        // 門市地圖選擇回調
        Route::post('/map-callback', [EcpayController::class, 'mapCallback'])
            ->name('map.callback');

        // 建立物流訂單
        Route::post('/create', [EcpayController::class, 'createLogisticsOrder'])
            ->name('create');

        // 物流狀態回調
        Route::post('/callback', [EcpayController::class, 'logisticsCallback'])
            ->name('callback');

        // 列印托運單
        Route::post('/print', [EcpayController::class, 'printLogistics'])
            ->name('print');
    });

    /*
    |--------------------------------------------------------------------------
    | 發票相關 API
    |--------------------------------------------------------------------------
    */
    Route::prefix('invoice')->name('invoice.')->group(function () {
        // 驗證統一編號
        Route::post('/verify-tax-id', [EcpayController::class, 'verifyTaxId'])
            ->name('verify-tax-id');

        // 驗證手機條碼
        Route::post('/verify-mobile-carrier', [EcpayController::class, 'verifyMobileCarrier'])
            ->name('verify-mobile-carrier');

        // 驗證愛心碼
        Route::post('/verify-love-code', [EcpayController::class, 'verifyLoveCode'])
            ->name('verify-love-code');

        // 查詢發票狀態
        Route::get('/query/{invoiceNo}', [EcpayController::class, 'queryInvoice'])
            ->name('query');
    });
});
