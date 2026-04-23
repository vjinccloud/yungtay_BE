<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factory_product_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained('factories')->onDelete('cascade');
            $table->foreignId('product_service_id')->constrained('product_services')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['factory_id', 'product_service_id'], 'factory_service_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factory_product_service');
    }
};
