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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->json('title')->comment('標題（多語言）');
            $table->bigInteger('category_id')->nullable()->comment('分類ID');
            $table->date('publish_date')->nullable()->comment('刊登日期');
            $table->json('author')->nullable()->comment('作者（多語言）');
            $table->json('location')->nullable()->comment('地點（多語言）');
            $table->json('content')->comment('內容編輯器（多語言）');
            $table->json('tags')->nullable()->comment('標籤（多語言）');
            $table->boolean('is_active')->default(1);
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
