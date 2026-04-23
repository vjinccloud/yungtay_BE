<?php

use Illuminate\Support\Facades\Route;
use Modules\FactoryServiceSetting\Backend\Controller\FactoryServiceSettingController;

/*
|--------------------------------------------------------------------------
| FactoryServiceSetting Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('factory-service-settings')->name('admin.factory-service-settings.')->group(function () {
    Route::get('/', [FactoryServiceSettingController::class, 'index'])->name('index');
    Route::post('/', [FactoryServiceSettingController::class, 'store'])->name('store');
    Route::post('/toggle', [FactoryServiceSettingController::class, 'toggle'])->name('toggle');
});
