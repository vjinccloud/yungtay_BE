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
        Schema::table('view_demographics', function (Blueprint $table) {
            // 新增 category_id 欄位（在 episode_id 之後）
            $table->unsignedBigInteger('category_id')->after('episode_id')->nullable()->comment('分類ID');
            
            // 新增索引
            $table->index('category_id', 'idx_category_id');
            
            // 新增外鍵（關聯到 categories 表）
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('view_demographics', function (Blueprint $table) {
            // 移除外鍵
            $table->dropForeign(['category_id']);
            
            // 移除索引
            $table->dropIndex('idx_category_id');
            
            // 移除欄位
            $table->dropColumn('category_id');
        });
    }
};
