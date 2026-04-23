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
        Schema::create('admin_menu', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->smallInteger('parent_id')->comment('當為0時為頂層');
            $table->unsignedTinyInteger('type')->comment('0:不顯示 1:顯示在選單');
            $table->unsignedTinyInteger('level');
            $table->string('url')->nullable()->comment('連結網址');
            $table->string('url_name')->nullable()->comment('連結網址名稱');
            $table->string('icon_image')->nullable()->comment('圖標');
            $table->boolean('status')->default(true)->comment('是否啟用 0否 1是');
            $table->unsignedTinyInteger('seq')->default(0)->comment('排序');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_menu');
    }
};
