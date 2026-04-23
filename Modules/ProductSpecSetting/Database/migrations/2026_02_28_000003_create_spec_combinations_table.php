<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spec_combinations', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->nullable()->comment('SKU 編號');
            $table->decimal('price', 10, 2)->nullable()->comment('價格');
            $table->unsignedInteger('stock')->nullable()->comment('庫存數量');
            $table->string('combination_key')->comment('組合鍵（排序後的 value_id 組合，用於唯一識別）');
            $table->unsignedSmallInteger('seq')->default(0)->comment('排序');
            $table->boolean('status')->default(true)->comment('啟用狀態');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('combination_key');
            $table->index('sku');
            $table->index('status');
            $table->index('seq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spec_combinations');
    }
};
