<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('會員ID');
            $table->enum('content_type', ['articles', 'drama', 'program', 'live', 'radio'])->comment('內容類型');
            $table->unsignedBigInteger('content_id')->comment('內容ID');
            $table->timestamps();
            
            // 唯一約束：同一用戶不能重複收藏同一內容
            $table->unique(['user_id', 'content_type', 'content_id'], 'unique_collection');
            
            // 索引優化
            $table->index(['user_id', 'content_type'], 'idx_user_type');
            $table->index(['created_at'], 'idx_created_at');
            
            // 外鍵約束
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        
        // 設定表格註解
        DB::statement("ALTER TABLE user_collections COMMENT '會員收藏記錄表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_collections');
    }
};
