<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 重建 view_demographics 表，優化為完整的人口統計與基礎統計聚合表
     */
    public function up(): void
    {
        // 先刪除舊表
        Schema::dropIfExists('view_demographics');

        // 重建新表
        Schema::create('view_demographics', function (Blueprint $table) {
            $table->id();

            // 內容識別
            $table->string('content_type', 50)->comment('內容類型：article, drama, program, live, radio');
            $table->unsignedBigInteger('content_id')->comment('對應的內容 ID');
            $table->unsignedBigInteger('episode_id')->default(0)->comment('集數ID，0 表示主內容無集數');

            // 時間維度
            $table->date('date')->comment('統計日期');

            // ⚠️ 基礎統計（從 view_logs 直接計算）
            $table->unsignedInteger('total_views')->default(0)->comment('總觀看次數');
            $table->unsignedInteger('unique_views')->default(0)->comment('唯一觀看次數（IP+user_id 組合去重）');
            $table->unsignedInteger('member_views')->default(0)->comment('會員觀看次數');
            $table->unsignedInteger('guest_views')->default(0)->comment('訪客觀看次數');

            // 性別統計（只有男性和女性，未登入訪客不計入）
            $table->unsignedInteger('male_views')->default(0)->comment('男性觀看次數');
            $table->unsignedInteger('female_views')->default(0)->comment('女性觀看次數');

            // 年齡統計（修正後的區間）
            $table->unsignedInteger('age_0_10')->default(0)->comment('0-10歲觀看次數');
            $table->unsignedInteger('age_11_20')->default(0)->comment('11-20歲觀看次數');
            $table->unsignedInteger('age_21_30')->default(0)->comment('21-30歲觀看次數');
            $table->unsignedInteger('age_31_40')->default(0)->comment('31-40歲觀看次數');
            $table->unsignedInteger('age_41_50')->default(0)->comment('41-50歲觀看次數');
            $table->unsignedInteger('age_51_60')->default(0)->comment('51-60歲觀看次數');
            $table->unsignedInteger('age_61_plus')->default(0)->comment('61歲以上觀看次數');
            $table->unsignedInteger('unknown_age')->default(0)->comment('年齡未知觀看次數');

            // 男性年齡交叉統計
            $table->unsignedInteger('male_age_0_10')->default(0)->comment('男性 0-10歲');
            $table->unsignedInteger('male_age_11_20')->default(0)->comment('男性 11-20歲');
            $table->unsignedInteger('male_age_21_30')->default(0)->comment('男性 21-30歲');
            $table->unsignedInteger('male_age_31_40')->default(0)->comment('男性 31-40歲');
            $table->unsignedInteger('male_age_41_50')->default(0)->comment('男性 41-50歲');
            $table->unsignedInteger('male_age_51_60')->default(0)->comment('男性 51-60歲');
            $table->unsignedInteger('male_age_61_plus')->default(0)->comment('男性 61歲以上');

            // 女性年齡交叉統計
            $table->unsignedInteger('female_age_0_10')->default(0)->comment('女性 0-10歲');
            $table->unsignedInteger('female_age_11_20')->default(0)->comment('女性 11-20歲');
            $table->unsignedInteger('female_age_21_30')->default(0)->comment('女性 21-30歲');
            $table->unsignedInteger('female_age_31_40')->default(0)->comment('女性 31-40歲');
            $table->unsignedInteger('female_age_41_50')->default(0)->comment('女性 41-50歲');
            $table->unsignedInteger('female_age_51_60')->default(0)->comment('女性 51-60歲');
            $table->unsignedInteger('female_age_61_plus')->default(0)->comment('女性 61歲以上');

            $table->timestamps();

            // 唯一約束：episode_id 使用 0 而非 NULL，保證唯一性
            $table->unique(['content_type', 'content_id', 'episode_id', 'date'], 'uk_demographics');

            // 索引
            $table->index(['content_type', 'content_id'], 'idx_content');
            $table->index('date', 'idx_date');
            $table->index('episode_id', 'idx_episode');
            $table->index(['content_type', 'date'], 'idx_content_date');
            $table->index(['content_type', 'content_id', 'episode_id', 'date'], 'idx_content_episode_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * 還原成舊的表結構
     */
    public function down(): void
    {
        // 先刪除新表
        Schema::dropIfExists('view_demographics');

        // 還原成舊表結構
        Schema::create('view_demographics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('統計日期');
            $table->string('content_type', 50)->comment('內容類型：article, drama, program, live, radio');
            $table->unsignedBigInteger('content_id')->comment('對應的內容 ID');

            // 會員數據（可統計年齡性別）
            $table->unsignedBigInteger('member_views')->default(0)->comment('會員觀看數');
            $table->unsignedBigInteger('age_18_25')->default(0)->comment('18-25歲觀看數');
            $table->unsignedBigInteger('age_26_35')->default(0)->comment('26-35歲觀看數');
            $table->unsignedBigInteger('age_36_45')->default(0)->comment('36-45歲觀看數');
            $table->unsignedBigInteger('age_46_55')->default(0)->comment('46-55歲觀看數');
            $table->unsignedBigInteger('age_56_65')->default(0)->comment('56-65歲觀看數');
            $table->unsignedBigInteger('age_65_plus')->default(0)->comment('65歲以上觀看數');
            $table->unsignedBigInteger('age_unknown')->default(0)->comment('年齡未知觀看數');

            $table->unsignedBigInteger('gender_male')->default(0)->comment('男性觀看數');
            $table->unsignedBigInteger('gender_female')->default(0)->comment('女性觀看數');
            $table->unsignedBigInteger('gender_unknown')->default(0)->comment('性別未知觀看數');

            // 訪客數據（無法統計年齡性別）
            $table->unsignedBigInteger('guest_views')->default(0)->comment('訪客觀看數');

            $table->timestamps();

            // 唯一約束
            $table->unique(['date', 'content_type', 'content_id'], 'uk_demographics_unique');

            // 索引
            $table->index(['content_type', 'content_id'], 'idx_content_demographics');
            $table->index(['date', 'content_type'], 'idx_date_content_type');
            $table->index('member_views', 'idx_member_views');
        });
    }
};
