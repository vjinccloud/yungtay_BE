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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();

            // 基本資訊 (完全對應影音的欄位)
            $table->json('title')->comment('多語系標題 {"zh_TW": "中文標題", "en": "English Title"}');
            $table->json('description')->nullable()->comment('多語系描述/簡介');
            $table->json('cast')->nullable()->comment('多語系演員陣容');
            $table->json('crew')->nullable()->comment('多語系製作團隊');
            $table->json('tags')->nullable()->comment('多語系標籤');
            $table->json('other_info')->nullable()->comment('多語系其他資訊');

            // 分類與基本設定
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade')->comment('節目主分類ID');
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->onDelete('set null')->comment('節目子分類ID');
            $table->integer('season_number')->default(1)->comment('季數：1, 2, 3...');
            $table->year('release_year')->nullable()->comment('發行年份');

            // 狀態設定
            $table->boolean('is_active')->default(false)->comment('啟用狀態 (預設關閉)');

            // 發佈設定
            $table->date('published_date')->nullable()->comment('發佈日期');

            // 系統欄位
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->onDelete('set null')->comment('建立者');
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->onDelete('set null')->comment('更新者');
            $table->timestamps();

            // 索引
            $table->index(['category_id', 'is_active'], 'idx_category_active');
            $table->index(['subcategory_id', 'is_active'], 'idx_subcategory_active');
            $table->index(['season_number', 'is_active'], 'idx_season_active');
            $table->index(['release_year', 'is_active'], 'idx_year_active');
            $table->index('published_date', 'idx_published_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};