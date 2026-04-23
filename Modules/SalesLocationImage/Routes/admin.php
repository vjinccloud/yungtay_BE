<?php

use Illuminate\Support\Facades\Route;
use Modules\SalesLocationImage\Backend\Controller\SalesLocationImageController;

/*
|--------------------------------------------------------------------------
| SalesLocationImage Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('sales-location-image')->group(function () {
    Route::get('/', [SalesLocationImageController::class, 'edit'])->name('admin.sales-location-image');
    Route::put('/', [SalesLocationImageController::class, 'update'])->name('admin.sales-location-image.update');
});
