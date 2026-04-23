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
        Schema::create('view_logs', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 50)->comment('內容類型：article, drama, program, live, radio, drama_episode, program_episode');
            $table->unsignedBigInteger('content_id')->comment('對應的內容 ID');
            $table->unsignedBigInteger('episode_id')->nullable()->comment('集數 ID（如果適用）');
            $table->unsignedBigInteger('user_id')->nullable()->comment('用戶 ID（可為空，支援訪客）');
            $table->string('ip_address', 45)->nullable()->comment('IP 地址');
            $table->text('user_agent')->nullable()->comment('用戶代理');
            $table->timestamp('created_at')->useCurrent()->comment('觀看時間');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // 索引
            $table->index(['content_type', 'content_id'], 'idx_content');
            $table->index('user_id', 'idx_user');
            $table->index('created_at', 'idx_created_at');
            $table->index(['content_type', 'content_id', 'episode_id'], 'idx_content_episode');
            
            // 外鍵約束
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('view_logs');
    }
};
