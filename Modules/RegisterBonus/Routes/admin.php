<?php

use Illuminate\Support\Facades\Route;
use Modules\RegisterBonus\Backend\Controller\RegisterBonusController;

// ===== 註冊購物金設定 =====
Route::prefix('register-bonus')->group(function () {
    Route::get('/', [RegisterBonusController::class, 'edit'])->name('admin.register-bonus');
    Route::put('/', [RegisterBonusController::class, 'update'])->name('admin.register-bonus.update');
});
