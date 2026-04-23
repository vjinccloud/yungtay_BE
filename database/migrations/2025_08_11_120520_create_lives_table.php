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
        Schema::create('lives', function (Blueprint $table) {
            $table->id();
            $table->json('title')->comment('直播標題 (多語系)');
            $table->json('description')->nullable()->comment('直播描述 (多語系)');
            $table->string('youtube_url')->nullable()->comment('YouTube直播連結');
            $table->json('remarks')->nullable()->comment('備註 (多語系)');
            $table->boolean('is_active')->default(1)->comment('啟用狀態 (1:啟用, 0:停用)');
            $table->integer('sort_order')->default(0)->comment('排序順序');
            $table->bigInteger('created_by')->nullable()->comment('建立者ID');
            $table->bigInteger('updated_by')->nullable()->comment('更新者ID');
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lives');
    }
};
