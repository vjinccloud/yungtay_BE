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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type', 255)->comment('来源表');
            $table->bigInteger('attachable_id')->comment('来源表 ID');
            $table->string('image_type')->nullable()->comment('图像类型');
            $table->string('path', 255)->comment('图像路径');
            $table->string('filename', 255)->comment('图像文件名');
            $table->string('ext', 255)->comment('副檔名');
            $table->string('title')->nullable()->comment('标题');
            $table->integer('seq')->default(0)->comment('排序');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
        Schema::dropIfExists('image_files');
    }
};
