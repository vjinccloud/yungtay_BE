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
        Schema::create('radios', function (Blueprint $table) {
            $table->id();
            $table->json('title')->comment('標題（多語言）');
            $table->string('audio_url')->nullable()->comment('MP3檔案路徑或串流網址');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade')->comment('廣播分類ID');
            $table->date('publish_date')->nullable()->comment('上架日期');
            $table->boolean('is_active')->default(1)->comment('啟用狀態');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->bigInteger('created_by')->nullable()->comment('建立者');
            $table->bigInteger('updated_by')->nullable()->comment('更新者');
            $table->timestamps();
            
            // 索引
            $table->index('is_active');
            $table->index('sort_order');
            $table->index('publish_date');
            $table->index(['category_id', 'is_active'], 'idx_category_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radios');
    }
};
