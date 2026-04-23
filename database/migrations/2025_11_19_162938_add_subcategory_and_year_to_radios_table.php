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
        Schema::table('radios', function (Blueprint $table) {
            // 新增子分類欄位（非必填）
            $table->foreignId('subcategory_id')
                ->nullable()
                ->after('category_id')
                ->constrained('categories')
                ->nullOnDelete();

            // 新增描述欄位
            $table->json('description')
                ->nullable()
                ->after('title')
                ->comment('簡介（多語系）');

            // 新增年份欄位
            $table->smallInteger('year')
                ->nullable()
                ->after('description')
                ->comment('年份');

            // 新增季欄位
            $table->smallInteger('season')
                ->nullable()
                ->after('year')
                ->comment('季');

            // 新增索引
            $table->index('subcategory_id');
            $table->index('year');
            $table->index('season');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('radios', function (Blueprint $table) {
            // 先移除外鍵約束
            $table->dropForeign(['subcategory_id']);

            // 再移除欄位
            $table->dropColumn(['subcategory_id', 'description', 'year', 'season']);
        });
    }
};
