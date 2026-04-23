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
            // 基本資料欄位
            $table->string('username', 50)->unique()->nullable()->comment('會員帳號');
            $table->string('phone', 20)->nullable()->comment('手機號碼');
            $table->date('birthdate')->nullable()->comment('生日');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->comment('性別');
            
            // 地址欄位（JSON 格式）
            $table->json('address')->nullable()->comment('地址資訊 JSON');
            
            // 其他欄位
            $table->string('avatar')->nullable()->comment('大頭貼');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            
            // 第三方登入
            $table->string('google_id')->unique()->nullable()->comment('Google ID');
            $table->string('line_id')->unique()->nullable()->comment('LINE ID');
            
            // 登入記錄
            $table->timestamp('last_login_at')->nullable()->comment('最後登入時間');
            $table->integer('login_count')->default(0)->comment('登入次數');
            
            // 建立索引
            $table->index('username');
            $table->index('phone');
            $table->index('google_id');
            $table->index('line_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 移除索引
            $table->dropIndex(['username']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['google_id']);
            $table->dropIndex(['line_id']);
            $table->dropIndex(['is_active']);
            
            // 移除欄位
            $table->dropColumn([
                'username',
                'phone',
                'birthdate',
                'gender',
                'address',
                'avatar',
                'is_active',
                'google_id',
                'line_id',
                'last_login_at',
                'login_count',
            ]);
        });
    }
};