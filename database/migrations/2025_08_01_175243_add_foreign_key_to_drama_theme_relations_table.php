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
        Schema::table('drama_theme_relations', function (Blueprint $table) {
            // 加入外鍵約束，當影音被刪除時，自動刪除關聯記錄
            $table->foreign('drama_id')
                  ->references('id')
                  ->on('dramas')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drama_theme_relations', function (Blueprint $table) {
            // 移除外鍵約束
            $table->dropForeign(['drama_id']);
        });
    }
};