<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_skus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->json('spec_value_ids')->comment('規格值ID組合 e.g. [1,3]');
            $table->string('combination_label', 255)->nullable()->comment('規格組合文字 e.g. 紅色 / M');
            $table->string('sku', 100)->nullable()->comment('SKU 編號');
            $table->decimal('price', 10, 2)->default(0)->comment('價格');
            $table->integer('stock')->default(0)->comment('庫存');
            $table->boolean('status')->default(true)->comment('狀態');
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_skus');
    }
};
