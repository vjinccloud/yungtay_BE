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
        // 1. view_demographics 新增 subcategory_id 欄位
        Schema::table('view_demographics', function (Blueprint $table) {
            $table->unsignedBigInteger('subcategory_id')
                ->nullable()
                ->after('category_id')
                ->comment('子分類 ID（僅 drama/program 有值）');

            // 新增索引，提升子分類查詢效能
            $table->index(['content_type', 'subcategory_id', 'date'], 'idx_subcategory_stats');
        });

        // 2. category_aggregations 新增 subcategory_id 欄位
        Schema::table('category_aggregations', function (Blueprint $table) {
            $table->unsignedBigInteger('subcategory_id')
                ->nullable()
                ->after('category_id')
                ->comment('子分類 ID（僅 drama/program 有值）');

            // 移除舊的唯一索引（不含 subcategory_id）
            // 實際索引名稱是 uk_aggregation
            $table->dropUnique('uk_aggregation');

            // 新增包含 subcategory_id 的唯一索引
            $table->unique(
                ['content_type', 'category_id', 'subcategory_id', 'period_type', 'period_date'],
                'uk_aggregation'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. view_demographics 移除 subcategory_id 欄位
        Schema::table('view_demographics', function (Blueprint $table) {
            $table->dropIndex('idx_subcategory_stats');
            $table->dropColumn('subcategory_id');
        });

        // 2. category_aggregations 移除 subcategory_id 欄位
        Schema::table('category_aggregations', function (Blueprint $table) {
            // 移除包含 subcategory_id 的唯一索引
            $table->dropUnique('uk_aggregation');

            // 恢復舊的唯一索引（不含 subcategory_id）
            $table->unique(
                ['content_type', 'category_id', 'period_type', 'period_date'],
                'uk_aggregation'
            );

            $table->dropColumn('subcategory_id');
        });
    }
};
