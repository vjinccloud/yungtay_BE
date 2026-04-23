<?php

use Illuminate\Support\Facades\Route;
use Modules\NewsCategoryManagement\Backend\Controller\NewsCategoryManagementController;

// 最新消息分類管理（模組）
Route::group(['middleware' => ['permission:admin.news-categories']], function () {
    Route::put('news-categories/toggle-active', [NewsCategoryManagementController::class, 'toggleActive'])
        ->name('admin.news-categories.toggle-active')
        ->middleware('permission:admin.news-categories.edit');

    Route::put('news-categories/sort', [NewsCategoryManagementController::class, 'sort'])
        ->name('admin.news-categories.sort')
        ->middleware('permission:admin.news-categories.edit');

    Route::delete('news-categories/child/{id}', [NewsCategoryManagementController::class, 'deleteChild'])
        ->name('admin.news-categories.delete-child')
        ->middleware('permission:admin.news-categories.edit');

    resourceWithPermissions(
        'admin.',
        'news-categories',
        NewsCategoryManagementController::class,
        [
            'index' => 'admin.news-categories',
            'add' => 'admin.news-categories.add',
            'store' => 'admin.news-categories.add',
            'show' => 'admin.news-categories.edit',
            'edit' => 'admin.news-categories.edit',
            'update' => 'admin.news-categories.edit',
            'destroy' => 'admin.news-categories.delete'
        ]
    );
});
