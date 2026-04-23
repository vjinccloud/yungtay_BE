<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_video_settings', function (Blueprint $table) {
            $table->id();
            
            // 標題（多語言 JSON）
            $table->json('title')->nullable();
            
            // 中文版影片
            $table->string('video_zh_path')->nullable()->comment('中文版影片路徑');
            $table->string('video_zh_name')->nullable()->comment('中文版影片檔名');
            
            // 英文版影片
            $table->string('video_en_path')->nullable()->comment('英文版影片路徑');
            $table->string('video_en_name')->nullable()->comment('英文版影片檔名');

            // 排序與狀態
            $table->integer('sort')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否啟用');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_video_settings');
    }
};
