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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // 'google', 'line'
            $table->string('provider_id'); // 第三方平台的用戶 ID
            $table->string('provider_email')->nullable(); // 第三方平台的 email
            $table->string('provider_name')->nullable(); // 第三方平台的用戶名稱
            $table->string('provider_avatar')->nullable(); // 第三方平台的頭像
            $table->json('provider_data')->nullable(); // 完整的第三方用戶資料
            $table->timestamps();

            // 複合唯一索引：同一個第三方帳號只能綁定一個用戶
            $table->unique(['provider', 'provider_id']);
            // 索引：加速查詢
            $table->index(['user_id']);
            $table->index(['provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
