<?php

use Illuminate\Support\Facades\Route;
use Modules\HomeVideoSetting\Backend\Controller\HomeVideoSettingController;

/*
|--------------------------------------------------------------------------
| HomeVideoSetting Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('home-video-settings')->name('admin.home-video-settings.')->group(function () {
    Route::get('/', [HomeVideoSettingController::class, 'index'])->name('index');
    Route::get('/add', [HomeVideoSettingController::class, 'create'])->name('add');
    Route::post('/', [HomeVideoSettingController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [HomeVideoSettingController::class, 'edit'])->name('edit');
    Route::put('/{id}', [HomeVideoSettingController::class, 'update'])->name('update');
    Route::delete('/{id}', [HomeVideoSettingController::class, 'destroy'])->name('destroy');
    Route::post('/sort', [HomeVideoSettingController::class, 'updateSort'])->name('sort');
});
