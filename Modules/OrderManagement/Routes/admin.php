<?php

use Illuminate\Support\Facades\Route;
use Modules\OrderManagement\Backend\Controller\OrderController;

/*
|--------------------------------------------------------------------------
| OrderManagement Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('orders')->name('admin.orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{id}', [OrderController::class, 'show'])->name('show');
    Route::post('/batch-status', [OrderController::class, 'batchUpdateStatus'])->name('batch-status');
    Route::post('/{id}/status', [OrderController::class, 'updateStatus'])->name('update-status');
    Route::post('/{id}/note', [OrderController::class, 'updateNote'])->name('update-note');
});
