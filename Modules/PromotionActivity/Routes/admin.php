<?php

use Illuminate\Support\Facades\Route;
use Modules\PromotionActivity\Backend\Controller\PromotionActivityController;

// ===== 滿額免運設定 =====
Route::prefix('promotion-activity')->group(function () {
    Route::get('/', [PromotionActivityController::class, 'edit'])->name('admin.promotion-activity');
    Route::put('/', [PromotionActivityController::class, 'update'])->name('admin.promotion-activity.update');
});
