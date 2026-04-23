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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('profile_completed')->default(1)->after('is_active')->comment('個人資料是否完整（1:完整, 0:未完整）');
            
            // 移除第三方登入 ID 欄位（改用 social_accounts 表格管理）
            if (Schema::hasColumn('users', 'google_id')) {
                $table->dropColumn('google_id');
            }
            if (Schema::hasColumn('users', 'line_id')) {
                $table->dropColumn('line_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 移除 profile_completed 欄位
            if (Schema::hasColumn('users', 'profile_completed')) {
                $table->dropColumn('profile_completed');
            }
           
            // 恢復第三方登入 ID 欄位
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'line_id')) {
                $table->string('line_id')->nullable();
            }
        });
    }
};
