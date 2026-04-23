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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->comment('通知類型：customer_service, system, member_action 等');
            $table->enum('recipient_type', ['admin', 'member'])->comment('接收者類型：admin, member');
            $table->unsignedBigInteger('recipient_id')->nullable()->comment('接收者ID：admin_id 或 user_id，null 代表全體');
            $table->string('title')->comment('通知標題');
            $table->text('message')->comment('通知內容');
            $table->json('data')->nullable()->comment('額外資料，如相關記錄的 ID');
            $table->timestamp('read_at')->nullable()->comment('已讀時間，null = 未讀');
            $table->timestamps();

            // 建立索引優化查詢效能
            $table->index(['recipient_type', 'recipient_id', 'read_at'], 'notifications_recipient_read_index');
            $table->index(['type', 'created_at'], 'notifications_type_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
