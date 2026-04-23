<?php

use Illuminate\Support\Facades\Route;
use Modules\HomeImageSetting\Backend\Controller\HomeImageSettingController;

/*
|--------------------------------------------------------------------------
| HomeImageSetting Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('home-image-setting')->group(function () {
    Route::get('/', [HomeImageSettingController::class, 'edit'])->name('admin.home-image-setting');
    Route::put('/', [HomeImageSettingController::class, 'update'])->name('admin.home-image-setting.update');
});
