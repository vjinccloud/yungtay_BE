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
        Schema::create('radio_episodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('radio_id')->nullable()->comment('廣播ID（可為null支援暫存集數）');
            $table->unsignedTinyInteger('season')->default(1)->comment('季數');
            $table->unsignedInteger('episode_number')->default(1)->comment('集數編號');
            $table->string('audio_path', 255)->nullable()->comment('音訊檔案路徑');
            $table->integer('duration')->nullable()->comment('時長（秒）');
            $table->json('duration_text')->nullable()->comment('時長文字（多語系，如：66分鐘）');
            $table->json('description')->nullable()->comment('單集簡介（多語系）');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->tinyInteger('is_active')->default(1)->comment('是否啟用');
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            // 外鍵約束（允許 null）
            $table->foreign('radio_id')->references('id')->on('radios')->onDelete('cascade');

            // 索引
            $table->index('radio_id');
            $table->index('is_active');
            $table->index('sort_order');
            $table->index(['radio_id', 'season', 'episode_number'], 'radio_season_episode_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radio_episodes');
    }
};
