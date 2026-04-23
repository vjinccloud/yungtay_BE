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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->json('title')->comment('多語系標題');
            $table->json('content')->comment('多語系內文');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->date('published_date')->nullable()->comment('上架日期');

            // 新增管理員欄位
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者管理員ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('最後修改者管理員ID');

            $table->timestamps();

            // 外鍵約束 (假設管理員表是 users 或 admins)
            $table->foreign('created_by')->references('id')->on('admin_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('admin_users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
