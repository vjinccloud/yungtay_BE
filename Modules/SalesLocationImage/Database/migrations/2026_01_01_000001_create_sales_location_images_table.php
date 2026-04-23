<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SalesLocationImage 銷售據點圖片管理 - 資料表遷移
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_location_images', function (Blueprint $table) {
            $table->id();
            
            // 標題（多語言 JSON）
            $table->json('title')->nullable()->comment('標題（多語言）');
            
            // 排序
            $table->integer('sort')->default(0)->comment('排序');
            
            // 啟用狀態
            $table->boolean('is_enabled')->default(true)->comment('啟用狀態');
            
            // 建立者/更新者
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_location_images');
    }
};
