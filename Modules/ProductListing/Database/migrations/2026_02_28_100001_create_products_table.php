<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('商品名稱 (translatable)');
            $table->tinyInteger('status')->default(1)->comment('狀態: 1=上架 0=下架');
            $table->decimal('price', 10, 2)->default(0)->comment('售價');
            $table->boolean('is_hot')->default(false)->comment('是否熱銷');
            $table->unsignedBigInteger('spec_combination_id')->nullable()->comment('關聯規格組合ID');
            $table->json('description')->nullable()->comment('商品描述 (translatable, HTML)');
            $table->integer('seq')->default(0)->comment('排序');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('spec_combination_id')
                  ->references('id')
                  ->on('spec_combinations')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
