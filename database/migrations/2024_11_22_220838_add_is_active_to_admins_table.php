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
        Schema::table('admin_users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->comment('啟用狀態');
            $table->boolean('is_dark')->default(false)->comment('是否啟用暗色系後台');
            $table->timestamp('last_login_at')->nullable()->comment('紀錄最後登入時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropColumn('is_dark');
            $table->dropColumn('last_login_at');
        });
    }
};
