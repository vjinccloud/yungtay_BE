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
        Schema::create('product_front_menu', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('front_menu_id');
            $table->primary(['product_id', 'front_menu_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('front_menu_id')->references('id')->on('front_menus')->cascadeOnDelete();
        });

        // 移除舊的單一外鍵欄位
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['front_menu_id']);
            $table->dropColumn('front_menu_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('front_menu_id')->nullable()->after('spec_combination_id');
            $table->foreign('front_menu_id')->references('id')->on('front_menus')->nullOnDelete();
        });
        Schema::dropIfExists('product_front_menu');
    }
};
