<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_services', function (Blueprint $table) {
            $table->id();
            $table->json('name')->nullable()->comment('名稱（多語言）');
            $table->integer('sort')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否啟用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_services');
    }
};
