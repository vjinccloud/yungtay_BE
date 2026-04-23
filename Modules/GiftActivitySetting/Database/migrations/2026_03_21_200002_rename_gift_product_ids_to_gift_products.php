<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_activities', function (Blueprint $table) {
            $table->renameColumn('gift_product_ids', 'gift_products');
        });
    }

    public function down(): void
    {
        Schema::table('gift_activities', function (Blueprint $table) {
            $table->renameColumn('gift_products', 'gift_product_ids');
        });
    }
};
