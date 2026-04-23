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
        Schema::create('view_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 50)->comment('內容類型：article, drama, program, live, radio');
            $table->unsignedBigInteger('content_id')->comment('對應的內容 ID');
            $table->unsignedBigInteger('episode_id')->nullable()->comment('集數 ID（如果適用）');
            $table->unsignedBigInteger('total_views')->default(0)->comment('總觀看數');
            $table->unsignedBigInteger('unique_views')->default(0)->comment('唯一觀看數');
            $table->unsignedBigInteger('daily_views')->default(0)->comment('當日觀看數');
            $table->date('last_view_date')->nullable()->comment('最後觀看日期');
            $table->timestamps(); // 自動加入 created_at 和 updated_at
            
            // 唯一約束
            $table->unique(['content_type', 'content_id', 'episode_id'], 'uk_content_unique');
            
            // 索引
            $table->index(['content_type', 'total_views'], 'idx_ranking');
            $table->index(['content_type', 'content_id'], 'idx_content_stats');
            $table->index('last_view_date', 'idx_last_view_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('view_statistics');
    }
};
