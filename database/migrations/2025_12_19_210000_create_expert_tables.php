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
        // 專家領域（標籤式）
        Schema::create('expert_fields', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('領域名稱（多語系）');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('啟用狀態');
            $table->timestamps();

            $table->index('sort_order');
            $table->index('is_active');
        });

        // 專家分類
        Schema::create('expert_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('分類名稱（多語系）');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('啟用狀態');
            $table->timestamps();

            $table->index('sort_order');
            $table->index('is_active');
        });

        // 專家
        Schema::create('experts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('expert_categories')->nullOnDelete()->comment('專家分類');
            $table->json('name')->comment('專家姓名（多語系）');
            $table->json('title')->nullable()->comment('頭銜/職稱（多語系）');
            $table->json('specialty')->nullable()->comment('專業領域說明（多語系）');
            $table->json('bio')->nullable()->comment('簡介（多語系）');
            $table->boolean('is_featured')->default(false)->comment('是否為主打專家');
            $table->boolean('is_active')->default(true)->comment('啟用狀態');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('修改者');
            $table->timestamps();

            $table->index('category_id');
            $table->index('is_featured');
            $table->index('is_active');
            $table->index('sort_order');
        });

        // 專家與領域的關聯表（多對多）
        Schema::create('expert_field_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained('experts')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('expert_fields')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['expert_id', 'field_id']);
        });

        // 專家文章（生命故事）
        Schema::create('expert_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained('experts')->cascadeOnDelete()->comment('所屬專家');
            $table->json('title')->comment('文章標題（多語系）');
            $table->json('content')->nullable()->comment('文章內容（多語系）');
            $table->string('description')->nullable()->comment('文章描述');
            $table->string('tags')->nullable()->comment('標籤');
            $table->date('published_date')->nullable()->comment('發布日期');
            $table->boolean('is_active')->default(true)->comment('啟用狀態');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('修改者');
            $table->timestamps();

            $table->index('expert_id');
            $table->index('published_date');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expert_articles');
        Schema::dropIfExists('expert_field_relations');
        Schema::dropIfExists('experts');
        Schema::dropIfExists('expert_categories');
        Schema::dropIfExists('expert_fields');
    }
};
