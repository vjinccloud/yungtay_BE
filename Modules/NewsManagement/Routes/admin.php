<?php

use Illuminate\Support\Facades\Route;
use Modules\NewsManagement\Backend\Controller\NewsManagementController;

// 最新消息管理（模組）
Route::group(['middleware' => ['permission:admin.news-management.index']], function () {
    Route::get('news-management', [NewsManagementController::class, 'index'])
        ->name('admin.news-management.index');

    Route::get('news-management/create', [NewsManagementController::class, 'create'])
        ->name('admin.news-management.create')
        ->middleware('permission:admin.news-management.create');

    Route::post('news-management', [NewsManagementController::class, 'store'])
        ->name('admin.news-management.store')
        ->middleware('permission:admin.news-management.create');

    // 切換狀態（放在 {id} 路由之前避免衝突）
    Route::put('news-management/toggle-active', [NewsManagementController::class, 'toggleActive'])
        ->name('admin.news-management.toggle-active')
        ->middleware('permission:admin.news-management.edit');

    Route::put('news-management/toggle-homepage-featured', [NewsManagementController::class, 'toggleHomepageFeatured'])
        ->name('admin.news-management.toggle-homepage-featured')
        ->middleware('permission:admin.news-management.edit');

    Route::put('news-management/toggle-pinned', [NewsManagementController::class, 'togglePinned'])
        ->name('admin.news-management.toggle-pinned')
        ->middleware('permission:admin.news-management.edit');

    Route::get('news-management/{id}/edit', [NewsManagementController::class, 'edit'])
        ->name('admin.news-management.edit')
        ->middleware('permission:admin.news-management.edit');

    Route::put('news-management/{id}', [NewsManagementController::class, 'update'])
        ->name('admin.news-management.update')
        ->middleware('permission:admin.news-management.edit');

    Route::delete('news-management/{id}', [NewsManagementController::class, 'destroy'])
        ->name('admin.news-management.destroy')
        ->middleware('permission:admin.news-management.destroy');
});
