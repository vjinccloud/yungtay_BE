<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 修正 Radio 主題系統，統一與 Drama/Program 主題結構：
     *
     * radio_themes 表：
     * 1. title → name（主題名稱）
     * 2. sort → sort_order（排序）
     * 3. 移除 poster_desktop 和 poster_mobile（保持與 Drama/Program 一致）
     *
     * radio_theme_relations 表：
     * 1. radio_theme_id → theme_id（主題ID）
     * 2. sort → sort_order（排序）
     * 3. radio_id 改為 nullable（與 Drama/Program 保持一致）
     */
    public function up(): void
    {
        // 修正 radio_themes 表
        Schema::table('radio_themes', function (Blueprint $table) {
            $table->renameColumn('title', 'name');
            $table->renameColumn('sort', 'sort_order');
            $table->dropColumn(['poster_desktop', 'poster_mobile']);
        });

        // 修正 radio_theme_relations 表
        Schema::table('radio_theme_relations', function (Blueprint $table) {
            // 先刪除外鍵約束
            $table->dropForeign(['radio_theme_id']);
            $table->dropForeign(['radio_id']);

            // 重命名欄位
            $table->renameColumn('radio_theme_id', 'theme_id');
            $table->renameColumn('sort', 'sort_order');

            // 修改 radio_id 為 nullable
            $table->unsignedBigInteger('radio_id')->nullable()->change();
        });

        // 重新建立外鍵約束
        Schema::table('radio_theme_relations', function (Blueprint $table) {
            $table->foreign('theme_id')->references('id')->on('radio_themes')->onDelete('cascade');
            $table->foreign('radio_id')->references('id')->on('radios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 恢復 radio_theme_relations 表
        Schema::table('radio_theme_relations', function (Blueprint $table) {
            // 先刪除外鍵約束
            $table->dropForeign(['theme_id']);
            $table->dropForeign(['radio_id']);

            // 恢復欄位名稱
            $table->renameColumn('theme_id', 'radio_theme_id');
            $table->renameColumn('sort_order', 'sort');

            // 恢復 radio_id 為 not nullable
            $table->unsignedBigInteger('radio_id')->nullable(false)->change();
        });

        // 重新建立外鍵約束
        Schema::table('radio_theme_relations', function (Blueprint $table) {
            $table->foreign('radio_theme_id')->references('id')->on('radio_themes')->onDelete('cascade');
            $table->foreign('radio_id')->references('id')->on('radios')->onDelete('cascade');
        });

        // 恢復 radio_themes 表
        Schema::table('radio_themes', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
            $table->renameColumn('sort_order', 'sort');
            $table->string('poster_desktop', 255)->nullable()->comment('桌面版橫幅');
            $table->string('poster_mobile', 255)->nullable()->comment('手機版橫幅');
        });
    }
};