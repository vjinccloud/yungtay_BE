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
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 50)
                  ->comment('分類類型：drama, program, radio');
            $table->json('name')
                  ->comment('分類名稱（多語系）');
            $table->string('slug', 255)
                  ->comment('URL 標識');
            $table->json('description')
                  ->nullable()
                  ->comment('分類描述（多語系）');
            $table->string('image', 255)
                  ->nullable()
                  ->comment('分類圖片');
            $table->unsignedBigInteger('parent_id')
                  ->nullable()
                  ->comment('父分類ID');
            $table->tinyInteger('level')
                  ->default(0)
                  ->comment('層級');
            $table->integer('seq')
                  ->default(0)
                  ->comment('排序');
            $table->tinyInteger('status')
                  ->default(1)
                  ->comment('狀態：1=啟用，0=停用');
            $table->timestamps();

            // 索引
            $table->index('type', 'categories_type_index');
            $table->index('parent_id', 'categories_parent_id_index');
            $table->index('slug', 'categories_slug_index');
            $table->index('status', 'categories_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
