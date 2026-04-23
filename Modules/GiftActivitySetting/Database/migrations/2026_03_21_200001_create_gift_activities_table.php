<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_activities', function (Blueprint $table) {
            $table->id();

            // 基本資訊
            $table->string('title')->comment('活動名稱');
            $table->date('start_date')->nullable()->comment('活動開始日期');
            $table->date('end_date')->nullable()->comment('活動結束日期');
            $table->string('status', 20)->default('draft')->comment('狀態：active=啟用, draft=草稿');

            // 條件設定
            $table->string('condition_type', 30)->default('all')->comment('條件類型：all=全部, order_total=全單滿多少, category=商品分類');
            $table->unsignedInteger('condition_amount')->nullable()->comment('滿足金額');
            $table->json('condition_category_ids')->nullable()->comment('指定商品分類 ID');

            // 贈品選擇
            $table->json('gift_product_ids')->nullable()->comment('可發送的贈品商品 ID');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_activities');
    }
};
