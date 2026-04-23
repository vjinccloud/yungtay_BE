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
        Schema::create('view_rankings', function (Blueprint $table) {
            $table->id();
            $table->enum('period_type', ['daily', 'weekly', 'monthly'])->comment('排行週期類型');
            $table->date('period_date')->comment('週期日期');
            $table->string('content_type', 50)->comment('內容類型');
            $table->unsignedBigInteger('content_id')->comment('內容 ID');
            $table->unsignedInteger('ranking')->comment('排名');
            $table->unsignedBigInteger('view_count')->comment('觀看數');
            $table->unsignedBigInteger('unique_count')->default(0)->comment('唯一觀看數');
            $table->decimal('growth_rate', 8, 2)->default(0)->comment('成長率(%)');
            $table->timestamps();
            
            // 唯一約束
            $table->unique(['period_type', 'period_date', 'content_type', 'content_id'], 'uk_ranking_unique');
            
            // 索引
            $table->index(['period_type', 'period_date', 'content_type', 'ranking'], 'idx_ranking_list');
            $table->index(['content_type', 'view_count'], 'idx_content_views');
            $table->index('period_date', 'idx_period_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('view_rankings');
    }
};
