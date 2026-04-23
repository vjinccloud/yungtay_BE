<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            
            // 多語言欄位 - 按照圖片顯示的欄位
            $table->json('title')->comment('標題（多語言）{"zh_TW": "中文標題", "en": "English Title"}');
            $table->json('subtitle_1')->nullable()->comment('簡述1-蓋字區塊（多語言）');
            $table->json('subtitle_2')->nullable()->comment('簡述2（多語言）');
            $table->string('url')->nullable()->comment('連結網址');
            $table->json('tags')->nullable()->comment('標籤（多語言）');
            
            // 排序與狀態
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('啟用狀態');
            
            // 系統欄位
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->onDelete('set null')->comment('建立者');
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->onDelete('set null')->comment('更新者');
            $table->timestamps();
            
            // 索引
            $table->index(['is_active', 'sort_order'], 'idx_active_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
