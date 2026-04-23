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
        Schema::create('module_descriptions', function (Blueprint $table) {
            $table->id();
            
            // 模組識別
            $table->string('module_key', 50)->unique()->comment('模組標識key');
            
            // 多語言欄位
            $table->json('meta_title')->nullable()->comment('標題（多語言）');
            $table->json('meta_description')->nullable()->comment('SEO描述（多語言）');
            $table->json('meta_keywords')->nullable()->comment('SEO關鍵字（多語言）');
            
            // 管理欄位
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新者');
            
            // 時間戳
            $table->timestamps();
            
            // 外鍵約束
            $table->foreign('created_by')->references('id')->on('admin_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('admin_users')->onDelete('set null');
            
            // 索引
            $table->index('module_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_descriptions');
    }
};
