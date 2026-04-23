<?php

use Illuminate\Support\Facades\Route;
use Modules\ProductService\Backend\Controller\ProductServiceController;

/*
|--------------------------------------------------------------------------
| ProductService Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('product-services')->name('admin.product-services.')->group(function () {
    Route::get('/', [ProductServiceController::class, 'index'])->name('index');
    Route::get('/add', [ProductServiceController::class, 'create'])->name('add');
    Route::post('/', [ProductServiceController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ProductServiceController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ProductServiceController::class, 'update'])->name('update');
    Route::delete('/{id}', [ProductServiceController::class, 'destroy'])->name('destroy');
    Route::put('/toggle-active', [ProductServiceController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/sort', [ProductServiceController::class, 'updateSort'])->name('sort');
});
