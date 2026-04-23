<?php

use Illuminate\Support\Facades\Route;
use Modules\RewardActivity\Backend\Controller\RewardActivityController;

Route::prefix('reward-activities')->name('admin.reward-activities.')->group(function () {
    Route::get('/',          [RewardActivityController::class, 'index'])->name('index');
    Route::get('/create',    [RewardActivityController::class, 'create'])->name('create');
    Route::post('/',         [RewardActivityController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [RewardActivityController::class, 'edit'])->name('edit');
    Route::put('/{id}',      [RewardActivityController::class, 'update'])->name('update');
    Route::delete('/{id}',   [RewardActivityController::class, 'destroy'])->name('destroy');
});
