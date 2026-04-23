<?php

use Illuminate\Support\Facades\Route;
use Modules\BannerManagement\Backend\Controller\BannerManagementController;

Route::prefix('banner-management')->name('admin.banner-management.')->group(function () {
    Route::get('/',          [BannerManagementController::class, 'index'])->name('index');
    Route::get('/create',    [BannerManagementController::class, 'create'])->name('create');
    Route::post('/',         [BannerManagementController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [BannerManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}',      [BannerManagementController::class, 'update'])->name('update');
    Route::delete('/{id}',   [BannerManagementController::class, 'destroy'])->name('destroy');
});
