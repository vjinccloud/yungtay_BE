<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IntroVideo 片頭動畫 - 資料表遷移
 * 
 * 單一設定頁面，資料表只會有一筆資料
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intro_videos', function (Blueprint $table) {
            $table->id();
            
            // 影片路徑
            $table->string('video_path')->nullable()->comment('影片路徑');
            
            // 影片原始檔名
            $table->string('video_original_name')->nullable()->comment('影片原始檔名');
            
            // 影片大小（bytes）
            $table->unsignedBigInteger('video_size')->nullable()->comment('影片大小');
            
            // 啟用狀態
            $table->boolean('is_active')->default(true)->comment('啟用狀態');
            
            // 建立者/更新者
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intro_videos');
    }
};
