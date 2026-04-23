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
        Schema::create('member_notifications', function (Blueprint $table) {
            $table->id();

            // 多語系標題和內容（JSON 格式）
            $table->json('title')->comment('多語系標題（JSON格式：{"zh_TW":"中文","en":"English"}）');
            $table->json('message')->comment('多語系內容（JSON格式：{"zh_TW":"中文","en":"English"}）');

            // 發送對象設定
            $table->enum('target_type', ['all', 'specific'])
                  ->default('all')
                  ->comment('發送對象：all=全體會員，specific=特定會員');

            // 發送模式設定
            $table->enum('send_mode', ['immediate', 'scheduled'])
                  ->default('immediate')
                  ->comment('發送模式：immediate=立即發送，scheduled=排程發送');

            // 排程發送時間
            $table->timestamp('scheduled_at')->nullable()->comment('排程發送時間');

            // 通知狀態
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])
                  ->default('draft')
                  ->comment('通知狀態：draft=草稿，scheduled=已排程，sending=發送中，sent=已發送，failed=發送失敗');

            // 實際發送時間
            $table->timestamp('sent_at')->nullable()->comment('實際發送時間');

            // 建立者和更新者（配合 BaseModelTrait）
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新者ID');

            $table->timestamps();

            // 索引設定
            $table->index('status', 'idx_member_notifications_status');
            $table->index('target_type', 'idx_member_notifications_target_type');
            $table->index('send_mode', 'idx_member_notifications_send_mode');
            $table->index('scheduled_at', 'idx_member_notifications_scheduled_at');
            $table->index('sent_at', 'idx_member_notifications_sent_at');
            $table->index('created_by', 'idx_member_notifications_created_by');

            // 外鍵約束
            $table->foreign('created_by')->references('id')->on('admin_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('admin_users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_notifications');
    }
};
