<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 建立 category_aggregations 表，用於分類統計聚合
     */
    public function up(): void
    {
        Schema::create('category_aggregations', function (Blueprint $table) {
            $table->id();

            // 核心欄位
            $table->string('content_type', 50)->comment('drama/program/radio/article (效能優化)');
            $table->unsignedBigInteger('category_id')->comment('分類ID（連結 categories 表）');

            // 時間維度
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'all_time'])->comment('統計時段類型');
            $table->date('period_date')->nullable()->comment('daily/weekly/monthly 的起始日期，all_time 為 NULL');

            // 基礎統計（從 view_demographics 聚合）
            $table->unsignedBigInteger('total_views')->default(0)->comment('總觀看次數');
            $table->unsignedBigInteger('unique_views')->default(0)->comment('唯一觀看次數');
            $table->unsignedBigInteger('member_views')->default(0)->comment('會員觀看次數');
            $table->unsignedBigInteger('guest_views')->default(0)->comment('訪客觀看次數');

            // 性別統計（只有男性和女性）
            $table->unsignedBigInteger('male_views')->default(0)->comment('男性觀看次數');
            $table->unsignedBigInteger('female_views')->default(0)->comment('女性觀看次數');

            // 年齡統計
            $table->unsignedBigInteger('age_0_10')->default(0)->comment('0-10歲');
            $table->unsignedBigInteger('age_11_20')->default(0)->comment('11-20歲');
            $table->unsignedBigInteger('age_21_30')->default(0)->comment('21-30歲');
            $table->unsignedBigInteger('age_31_40')->default(0)->comment('31-40歲');
            $table->unsignedBigInteger('age_41_50')->default(0)->comment('41-50歲');
            $table->unsignedBigInteger('age_51_60')->default(0)->comment('51-60歲');
            $table->unsignedBigInteger('age_61_plus')->default(0)->comment('61歲以上');
            $table->unsignedBigInteger('unknown_age')->default(0)->comment('年齡未知');

            // 男性年齡交叉統計
            $table->unsignedBigInteger('male_age_0_10')->default(0);
            $table->unsignedBigInteger('male_age_11_20')->default(0);
            $table->unsignedBigInteger('male_age_21_30')->default(0);
            $table->unsignedBigInteger('male_age_31_40')->default(0);
            $table->unsignedBigInteger('male_age_41_50')->default(0);
            $table->unsignedBigInteger('male_age_51_60')->default(0);
            $table->unsignedBigInteger('male_age_61_plus')->default(0);

            // 女性年齡交叉統計
            $table->unsignedBigInteger('female_age_0_10')->default(0);
            $table->unsignedBigInteger('female_age_11_20')->default(0);
            $table->unsignedBigInteger('female_age_21_30')->default(0);
            $table->unsignedBigInteger('female_age_31_40')->default(0);
            $table->unsignedBigInteger('female_age_41_50')->default(0);
            $table->unsignedBigInteger('female_age_51_60')->default(0);
            $table->unsignedBigInteger('female_age_61_plus')->default(0);

            $table->timestamps();

            // 唯一約束
            $table->unique(['content_type', 'category_id', 'period_type', 'period_date'], 'uk_aggregation');

            // 索引
            $table->index(['content_type', 'period_type'], 'idx_content_period');
            $table->index('category_id', 'idx_category');
            $table->index('period_date', 'idx_period_date');
            $table->index(['category_id', 'period_type', 'period_date'], 'idx_category_period');

            // 外鍵
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_aggregations');
    }
};
