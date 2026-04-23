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
        Schema::table('news', function (Blueprint $table) {
            // 首頁曝光文章（最多4則）
            $table->boolean('is_homepage_featured')->default(false)->after('is_active')->comment('首頁曝光文章');
            // 最新消息置頂文章（最多3則）
            $table->boolean('is_pinned')->default(false)->after('is_homepage_featured')->comment('最新消息置頂文章');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['is_homepage_featured', 'is_pinned']);
        });
    }
};
