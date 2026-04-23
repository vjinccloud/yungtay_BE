<?php
// database/migrations/2025_07_15_000002_create_drama_episodes_table.php

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
        Schema::create('drama_episodes', function (Blueprint $table) {
            $table->id();

            // 關聯影音
            $table->foreignId('drama_id')->nullable()->constrained('dramas')->onDelete('cascade')->comment('影音ID');

            // 集數基本資訊
            $table->json('description')->nullable()->comment('多語系集數描述/簡介');
            $table->json('duration_text')->nullable()->comment('多語系單集片長（純文字欄位） {"zh_TW": "66分鐘", "en": "66 minutes"}');

            // 影音檔案設定 (二選一)
            $table->enum('video_type', ['youtube', 'upload'])->nullable()->comment('影片類型：youtube連結 或 直接上傳');
            $table->string('youtube_url', 500)->nullable()->comment('YouTube連結');
            $table->string('video_file_path')->nullable()->comment('上傳影片檔案路徑');

            // 檔案資訊 (僅上傳檔案時使用)
            $table->string('original_filename')->nullable()->comment('原始檔名');
            $table->decimal('file_size', 10, 2)->nullable()->comment('檔案大小(MB) - 限制1GB');
            $table->string('video_format', 20)->nullable()->comment('影片格式 (mp4, avi等)');

            // 狀態與排序
            $table->integer('seq')->default(0)->comment('排序 (集數順序，排序1=第1集)');

            // 系統欄位
            $table->foreignId('created_by')->nullable()->constrained('admin_users')->onDelete('set null')->comment('建立者');
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->onDelete('set null')->comment('更新者');
            $table->timestamps();

            // 索引
            $table->index(['drama_id', 'seq'], 'idx_drama_seq');
            $table->index('drama_id', 'idx_drama');
            $table->index('video_type', 'idx_video_type');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drama_episodes');
    }
};
