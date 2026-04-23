<?php

use Illuminate\Support\Facades\Route;
use Modules\OrderManagement\Backend\Controller\OrderApiController;

/*
|--------------------------------------------------------------------------
| 訂單 API 路由（前台）
|--------------------------------------------------------------------------
*/

Route::prefix('v1/orders')->name('api.orders.')->group(function () {
    // 取得選項（付款/物流/狀態）
    Route::get('/options', [OrderApiController::class, 'options'])->name('options');

    // 取得商品列表（下單用）
    Route::get('/products', [OrderApiController::class, 'products'])->name('products');

    // 建立訂單
    Route::post('/', [OrderApiController::class, 'store'])->name('store');

    // 查詢訂單（用訂單編號 + 手機）
    Route::get('/query', [OrderApiController::class, 'query'])->name('query');
});
