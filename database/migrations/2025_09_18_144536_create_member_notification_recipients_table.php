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
        Schema::create('member_notification_recipients', function (Blueprint $table) {
            $table->id();

            // 關聯到主通知表
            $table->unsignedBigInteger('member_notification_id')->comment('會員通知主表ID');

            // 關聯到會員表
            $table->unsignedBigInteger('user_id')->comment('會員ID');

            // 發送狀態
            $table->enum('sent_status', ['pending', 'sent', 'failed'])
                  ->default('pending')
                  ->comment('發送狀態：pending=待發送，sent=已發送，failed=發送失敗');

            // 個別發送時間
            $table->timestamp('sent_at')->nullable()->comment('個別發送時間');

            // 讀取狀態
            $table->enum('read_status', ['unread', 'read'])
                  ->default('unread')
                  ->comment('讀取狀態：unread=未讀，read=已讀');

            // 讀取時間
            $table->timestamp('read_at')->nullable()->comment('讀取時間');

            $table->timestamps();

            // 索引設定
            $table->index('member_notification_id', 'idx_recipients_notification_id');
            $table->index('user_id', 'idx_recipients_user_id');
            $table->index('sent_status', 'idx_recipients_sent_status');
            $table->index('read_status', 'idx_recipients_read_status');
            $table->index('sent_at', 'idx_recipients_sent_at');
            $table->index('read_at', 'idx_recipients_read_at');

            // 複合索引（提升查詢效能）
            $table->index(['user_id', 'read_status'], 'idx_recipients_user_read');
            $table->index(['member_notification_id', 'sent_status'], 'idx_recipients_notification_sent');

            // 唯一約束（避免重複發送給同一會員）
            $table->unique(['member_notification_id', 'user_id'], 'uk_recipients_notification_user');

            // 外鍵約束
            $table->foreign('member_notification_id')
                  ->references('id')
                  ->on('member_notifications')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_notification_recipients');
    }
};
