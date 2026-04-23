<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HomeImageSetting 首頁圖片設定 - 資料表遷移
 * 
 * 單一設定頁面，資料表只會有一筆資料
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_image_settings', function (Blueprint $table) {
            $table->id();
            
            // 標題（多語言 JSON）
            $table->json('title')->nullable()->comment('標題（多語言）');
            
            // 建立者/更新者
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_image_settings');
    }
};
