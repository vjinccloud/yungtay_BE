<?php

use Illuminate\Support\Facades\Route;
use Modules\ProductSpecSetting\Backend\Controller\ProductSpecController;

// ===== Inertia Page Routes =====
Route::prefix('product-spec-settings')->name('admin.product-spec-settings.')->group(function () {
    // 主頁（群組列表 + 組合列表）
    Route::get('/', [ProductSpecController::class, 'index'])->name('index');

    // 規格群組 CRUD
    Route::get('/groups/add', [ProductSpecController::class, 'createGroup'])->name('groups.add');
    Route::post('/groups', [ProductSpecController::class, 'storeGroup'])->name('groups.store');
    Route::get('/groups/{id}/edit', [ProductSpecController::class, 'editGroup'])->name('groups.edit');
    Route::put('/groups/{id}', [ProductSpecController::class, 'updateGroup'])->name('groups.update');
    Route::delete('/groups/{id}', [ProductSpecController::class, 'destroyGroup'])->name('groups.destroy');
    Route::put('/groups/toggle-active', [ProductSpecController::class, 'toggleGroupActive'])->name('groups.toggle-active');
    Route::post('/groups/sort', [ProductSpecController::class, 'updateGroupSort'])->name('groups.sort');

    // 規格組合
    Route::put('/combinations/toggle-active', [ProductSpecController::class, 'toggleCombinationActive'])->name('combinations.toggle-active');
});

// ===== API Routes (for Modal / AJAX) =====
Route::prefix('api/product-spec-settings')->name('admin.api.product-spec-settings.')->group(function () {
    // 群組
    Route::get('/groups', [ProductSpecController::class, 'apiGroupList'])->name('groups.list');
    Route::get('/groups/{id}', [ProductSpecController::class, 'apiGroupShow'])->name('groups.show');
    Route::post('/groups', [ProductSpecController::class, 'apiGroupStore'])->name('groups.store');
    Route::put('/groups/{id}', [ProductSpecController::class, 'apiGroupUpdate'])->name('groups.update');
    Route::delete('/groups/{id}', [ProductSpecController::class, 'apiGroupDestroy'])->name('groups.destroy');

    // 規格值
    Route::post('/groups/{groupId}/values', [ProductSpecController::class, 'apiValueStore'])->name('values.store');
    Route::put('/values/{id}', [ProductSpecController::class, 'apiValueUpdate'])->name('values.update');
    Route::delete('/values/{id}', [ProductSpecController::class, 'apiValueDestroy'])->name('values.destroy');
    Route::put('/values/toggle-active', [ProductSpecController::class, 'apiValueToggleActive'])->name('values.toggle-active');

    // 組合
    Route::get('/combinations', [ProductSpecController::class, 'apiCombinationList'])->name('combinations.list');
    Route::get('/combinations/{id}', [ProductSpecController::class, 'apiCombinationShow'])->name('combinations.show');
    Route::post('/combinations', [ProductSpecController::class, 'apiStoreCombination'])->name('combinations.store');
    Route::put('/combinations/{id}', [ProductSpecController::class, 'apiCombinationUpdate'])->name('combinations.update');
    Route::delete('/combinations/{id}', [ProductSpecController::class, 'apiCombinationDestroy'])->name('combinations.destroy');

    // 前台結構
    Route::get('/structure', [ProductSpecController::class, 'apiSpecStructure'])->name('structure');
});
