<?php

use Illuminate\Support\Facades\Route;
use Modules\HistoryOrder\Backend\Controller\HistoryOrderController;

/*
|--------------------------------------------------------------------------
| HistoryOrder Admin Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['permission:admin.history-order.index']], function () {
    Route::get('history-order', [HistoryOrderController::class, 'index'])
        ->name('admin.history-order.index');

    Route::get('history-order/export', [HistoryOrderController::class, 'export'])
        ->name('admin.history-order.export');

    Route::get('history-order/{id}', [HistoryOrderController::class, 'show'])
        ->name('admin.history-order.show');

    Route::get('history-order/{id}/export-pdf', [HistoryOrderController::class, 'exportPdf'])
        ->name('admin.history-order.export-pdf');
});
