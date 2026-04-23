<?php

use Illuminate\Support\Facades\Route;
use Modules\GiftActivitySetting\Backend\Controller\GiftActivityController;

Route::prefix('gift-activity-settings')->name('admin.gift-activity-settings.')->group(function () {
    Route::get('/',          [GiftActivityController::class, 'index'])->name('index');
    Route::get('/create',    [GiftActivityController::class, 'create'])->name('create');
    Route::post('/',         [GiftActivityController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [GiftActivityController::class, 'edit'])->name('edit');
    Route::put('/{id}',      [GiftActivityController::class, 'update'])->name('update');
    Route::delete('/{id}',   [GiftActivityController::class, 'destroy'])->name('destroy');
});
