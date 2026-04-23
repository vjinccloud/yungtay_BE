<?php

use Illuminate\Support\Facades\Route;
use Modules\MenuSetting\Backend\Controller\MenuSettingController;

/*
|--------------------------------------------------------------------------
| MenuSetting Admin Routes
|--------------------------------------------------------------------------
*/

// Inertia 頁面路由
Route::prefix('menu-settings')->name('admin.menu-settings.')->group(function () {
    Route::get('/', [MenuSettingController::class, 'index'])->name('index');
    Route::get('/add', [MenuSettingController::class, 'create'])->name('add');
    Route::post('/', [MenuSettingController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [MenuSettingController::class, 'edit'])->name('edit');
    Route::put('/{id}', [MenuSettingController::class, 'update'])->name('update');
    Route::delete('/{id}', [MenuSettingController::class, 'destroy'])->name('destroy');
    Route::put('/toggle-active', [MenuSettingController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/sort', [MenuSettingController::class, 'updateSort'])->name('sort');
});

// API 路由（供 Modal 彈窗使用）
Route::prefix('api/menu-settings')->name('admin.api.menu-settings.')->group(function () {
    Route::get('/', [MenuSettingController::class, 'apiList'])->name('list');
    Route::get('/parent-options', [MenuSettingController::class, 'apiParentOptions'])->name('parent-options');
    Route::get('/{id}/delete-info', [MenuSettingController::class, 'apiDeleteInfo'])->name('delete-info');
    Route::get('/{id}', [MenuSettingController::class, 'apiShow'])->name('show');
    Route::post('/', [MenuSettingController::class, 'apiStore'])->name('store');
    Route::put('/{id}', [MenuSettingController::class, 'apiUpdate'])->name('update');
    Route::delete('/{id}', [MenuSettingController::class, 'apiDestroy'])->name('destroy');
});
