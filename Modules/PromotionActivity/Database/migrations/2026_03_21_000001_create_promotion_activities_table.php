<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('活動標題');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->date('start_date')->comment('活動開始日期');
            $table->date('end_date')->comment('活動結束日期');
            $table->unsignedInteger('min_amount')->default(0)->comment('滿額金額');
            $table->unsignedInteger('discount_amount')->default(0)->comment('抵扣金額');
            $table->json('category_ids')->nullable()->comment('指定商品分類 ID');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_activities');
    }
};
