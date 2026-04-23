<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('articles', function (Blueprint $table) {
            // RSS 來源追蹤欄位
            $table->string('source_provider', 50)->nullable()->index()
                  ->comment('來源提供者（如 cna, reuters, afp）');
            
            $table->string('source_guid_hash', 64)->nullable()
                  ->comment('原始 RSS guid 的 SHA256 hash');
            
            $table->string('source_link', 1000)->nullable()
                  ->comment('原始新聞連結');
            
            $table->timestamp('source_published_at')->nullable()
                  ->comment('原始發布時間');
            
            $table->timestamp('source_modified_at')->nullable()
                  ->comment('原始修改時間（對應 dc:modified）');
            
            $table->unsignedSmallInteger('source_comments_count')->default(0)
                  ->comment('CNA 照片版本號（0=純文字, 1=第一批照片, 2=第二批照片）');
            
            // 複合唯一索引，防止同一來源的重複文章
            $table->unique(['source_provider', 'source_guid_hash'], 'articles_source_unique');
            
            // 加速查詢的索引
            $table->index('source_published_at');
            $table->index('source_modified_at');
        });
    }

    public function down()
    {
        Schema::table('articles', function (Blueprint $table) {
            // 先移除索引
            $table->dropUnique('articles_source_unique');
            $table->dropIndex(['source_provider']);
            $table->dropIndex(['source_published_at']);
            $table->dropIndex(['source_modified_at']);
            
            // 移除欄位
            $table->dropColumn([
                'source_provider',
                'source_guid_hash',
                'source_link',
                'source_published_at',
                'source_modified_at',
                'source_comments_count'
            ]);
        });
    }
};
