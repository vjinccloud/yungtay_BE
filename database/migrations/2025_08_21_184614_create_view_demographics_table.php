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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('view_demographics');
    }
};
