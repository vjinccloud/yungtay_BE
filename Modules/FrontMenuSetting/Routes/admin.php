<?php

use Illuminate\Support\Facades\Route;
use Modules\FrontMenuSetting\Backend\Controller\FrontMenuController;

/*
|--------------------------------------------------------------------------
| FrontMenuSetting Admin Routes
|--------------------------------------------------------------------------
*/

// Inertia 頁面路由
Route::prefix('front-menu-settings')->name('admin.front-menu-settings.')->group(function () {
    Route::get('/', [FrontMenuController::class, 'index'])->name('index');
    Route::get('/add', [FrontMenuController::class, 'create'])->name('add');
    Route::post('/', [FrontMenuController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [FrontMenuController::class, 'edit'])->name('edit');
    Route::put('/{id}', [FrontMenuController::class, 'update'])->name('update');
    Route::delete('/{id}', [FrontMenuController::class, 'destroy'])->name('destroy');
    Route::put('/toggle-active', [FrontMenuController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/sort', [FrontMenuController::class, 'updateSort'])->name('sort');
});

// API 路由（供 Modal 彈窗使用）
Route::prefix('api/front-menu-settings')->name('admin.api.front-menu-settings.')->group(function () {
    Route::get('/', [FrontMenuController::class, 'apiList'])->name('list');
    Route::get('/parent-options', [FrontMenuController::class, 'apiParentOptions'])->name('parent-options');
    Route::get('/frontend-tree', [FrontMenuController::class, 'apiFrontendTree'])->name('frontend-tree');
    Route::get('/{id}/delete-info', [FrontMenuController::class, 'apiDeleteInfo'])->name('delete-info');
    Route::get('/{id}', [FrontMenuController::class, 'apiShow'])->name('show');
    Route::post('/', [FrontMenuController::class, 'apiStore'])->name('store');
    Route::put('/{id}', [FrontMenuController::class, 'apiUpdate'])->name('update');
    Route::delete('/{id}', [FrontMenuController::class, 'apiDestroy'])->name('destroy');
});
