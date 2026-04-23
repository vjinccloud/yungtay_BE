<?php

use Illuminate\Support\Facades\Route;
use Modules\ProductListing\Backend\Controller\ProductController;

// ===== Inertia Page Routes =====
Route::prefix('product-listings')->name('admin.product-listings.')->group(function () {
    Route::get('/',              [ProductController::class, 'index'])->name('index');
    Route::get('/create',        [ProductController::class, 'create'])->name('create');
    Route::post('/',             [ProductController::class, 'store'])->name('store');
    Route::put('/toggle-active', [ProductController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/sort',         [ProductController::class, 'updateSort'])->name('sort');
    Route::get('/{id}/edit',     [ProductController::class, 'edit'])->name('edit');
    Route::put('/{id}',          [ProductController::class, 'update'])->name('update');
    Route::delete('/{id}',       [ProductController::class, 'destroy'])->name('destroy');
});

// ===== API Routes =====
Route::prefix('api/product-listings')->name('admin.api.product-listings.')->group(function () {
    Route::post('/generate-sku-matrix', [ProductController::class, 'apiGenerateSkuMatrix'])->name('generate-sku-matrix');
    Route::post('/upload-image',        [ProductController::class, 'apiUploadImage'])->name('upload-image');
});
