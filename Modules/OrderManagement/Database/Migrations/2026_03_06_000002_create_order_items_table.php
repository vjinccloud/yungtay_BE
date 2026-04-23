<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable()->comment('商品ID');
            $table->unsignedBigInteger('product_sku_id')->nullable()->comment('SKU ID');
            $table->string('product_name', 255)->comment('商品名稱（快照）');
            $table->string('product_sku_code', 100)->nullable()->comment('SKU 編號（快照）');
            $table->string('combination_label', 255)->nullable()->comment('規格組合文字（快照）');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('單價（快照）');
            $table->integer('quantity')->default(1)->comment('數量');
            $table->decimal('subtotal', 10, 2)->default(0)->comment('小計');
            $table->string('product_image', 500)->nullable()->comment('商品圖片（快照）');
            $table->timestamps();

            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
