<?php

use Illuminate\Support\Facades\Route;
use Modules\HistoryOrder\Backend\Controller\HistoryOrderApiController;

/*
|--------------------------------------------------------------------------
| 歷史訂單 API 路由
|--------------------------------------------------------------------------
*/

Route::prefix('v1/history-orders')->name('api.history-orders.')->group(function () {
    // 取得假資料（測試用）
    Route::get('/fake', [HistoryOrderApiController::class, 'fake'])->name('fake');

    // 用假資料批次寫入資料庫
    Route::post('/seed-fake', [HistoryOrderApiController::class, 'seedFake'])->name('seed-fake');

    // 新增歷史訂單
    Route::post('/', [HistoryOrderApiController::class, 'store'])->name('store');
});
