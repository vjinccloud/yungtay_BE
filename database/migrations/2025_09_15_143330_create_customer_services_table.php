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
        Schema::create('customer_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('會員ID（如果是會員送出）');
            $table->string('name', 100)->comment('聯絡人姓名');
            $table->string('email')->comment('電子郵件');
            $table->string('phone', 50)->nullable()->comment('聯絡電話');
            $table->string('address')->nullable()->comment('聯絡地址');
            $table->string('subject')->comment('主旨');
            $table->text('message')->comment('訊息內容');
            $table->text('admin_note')->nullable()->comment('管理員備註');
            $table->boolean('is_replied')->default(false)->comment('是否已回覆');
            $table->string('reply_subject')->nullable()->comment('回覆主旨');
            $table->text('reply_content')->nullable()->comment('回覆內容');
            $table->timestamp('replied_at')->nullable()->comment('回覆時間');
            $table->unsignedBigInteger('replied_by')->nullable()->comment('回覆者ID');
            $table->timestamps();

            // 索引
            $table->index('user_id');
            $table->index('is_replied');
            $table->index('created_at');

            // 外鍵
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('replied_by')->references('id')->on('admin_users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_services');
    }
};
