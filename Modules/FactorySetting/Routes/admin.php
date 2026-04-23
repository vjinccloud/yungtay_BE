<?php

use Illuminate\Support\Facades\Route;
use Modules\FactorySetting\Backend\Controllers\FactorySettingController;

/*
|--------------------------------------------------------------------------
| FactorySetting Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('factory-settings')->group(function () {
    Route::get('/', [FactorySettingController::class, 'index'])->name('admin.factory-settings.index');
    Route::get('/{id}/edit', [FactorySettingController::class, 'edit'])->name('admin.factory-settings.edit');
    Route::put('/{id}', [FactorySettingController::class, 'update'])->name('admin.factory-settings.update');
});
