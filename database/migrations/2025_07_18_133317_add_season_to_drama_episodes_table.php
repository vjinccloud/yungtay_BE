<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('drama_episodes', function (Blueprint $table) {
            // 在 drama_id 後面添加 season 欄位
            $table->integer('season')->default(1)->after('drama_id')->comment('季數（第幾季）');
            
            // 只移除 idx_drama_seq 索引，保留 idx_drama（外鍵需要）
            $table->dropIndex('idx_drama_seq');
            
            // 新的複合索引
            $table->index(['drama_id', 'season', 'seq'], 'idx_drama_season_seq');
            $table->index(['drama_id', 'season'], 'idx_drama_season');
        });
    }

    public function down()
    {
        Schema::table('drama_episodes', function (Blueprint $table) {
            // 恢復原來的索引
            $table->dropIndex('idx_drama_season_seq');
            $table->dropIndex('idx_drama_season');
            
            $table->index(['drama_id', 'seq'], 'idx_drama_seq');
            
            // 移除 season 欄位
            $table->dropColumn('season');
        });
    }
};