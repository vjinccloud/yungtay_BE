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
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token', 255);
            $table->timestamp('expires_at');
            $table->timestamps();
            
            // 外鍵約束
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // 索引
            $table->index('user_id');
            $table->index('token');
            $table->index('expires_at');
            
            // 每個用戶只能有一個有效的驗證 token
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
    }
};
