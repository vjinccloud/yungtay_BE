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
        Schema::create('list_area', function (Blueprint $table) {
            $table->smallIncrements('sn')->comment('區域編號');
            $table->smallInteger('city_sn')->unsigned()->default(0)->comment('縣市編號');
            $table->string('title', 120)->comment('區域名稱');
            $table->string('zipcode', 10)->comment('郵遞區號');
            
            $table->index('city_sn', 'idx_city_sn');
            $table->foreign('city_sn')->references('sn')->on('list_city')->onDelete('cascade');
            
            $table->comment('區域列表');
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_area');
    }
};