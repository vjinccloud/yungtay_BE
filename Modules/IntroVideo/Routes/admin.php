<?php

use Illuminate\Support\Facades\Route;
use Modules\IntroVideo\Backend\Controller\IntroVideoController;

/*
|--------------------------------------------------------------------------
| IntroVideo Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('intro-video')->group(function () {
    Route::get('/', [IntroVideoController::class, 'edit'])->name('admin.intro-video');
    Route::post('/', [IntroVideoController::class, 'update'])->name('admin.intro-video.update');
    Route::delete('/video', [IntroVideoController::class, 'deleteVideo'])->name('admin.intro-video.delete-video');
});
