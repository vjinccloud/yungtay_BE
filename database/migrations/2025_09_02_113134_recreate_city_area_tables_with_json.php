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
        // 暫時關閉外鍵檢查
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // 刪除舊表
        Schema::dropIfExists('list_area');
        Schema::dropIfExists('list_city');
        
        // 重新建立 list_city 表，使用 JSON 欄位
        Schema::create('list_city', function (Blueprint $table) {
            $table->smallIncrements('sn')->comment('城市編號');
            $table->json('title')->comment('城市名稱 (多語言)');
            
            $table->comment('縣市列表 (支援多語言)');
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
        
        // 重新建立 list_area 表，使用 JSON 欄位
        Schema::create('list_area', function (Blueprint $table) {
            $table->smallIncrements('sn')->comment('區域編號');
            $table->smallInteger('city_sn')->unsigned()->default(0)->comment('縣市編號');
            $table->json('title')->comment('區域名稱 (多語言)');
            $table->string('zipcode', 10)->comment('郵遞區號');
            
            $table->index('city_sn', 'idx_city_sn');
            $table->foreign('city_sn')->references('sn')->on('list_city')->onDelete('cascade');
            
            $table->comment('區域列表 (支援多語言)');
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
        
        // 重新開啟外鍵檢查
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 暫時關閉外鍵檢查
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // 刪除新表
        Schema::dropIfExists('list_area');
        Schema::dropIfExists('list_city');
        
        // 還原成原始的表結構
        Schema::create('list_city', function (Blueprint $table) {
            $table->smallIncrements('sn')->comment('城市編號');
            $table->string('title', 120)->comment('城市名稱');
            
            $table->comment('縣市列表');
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
        
        Schema::create('list_area', function (Blueprint $table) {
            $table->smallIncrements('sn')->comment('區域編號');
            $table->smallInteger('city_sn')->unsigned()->default(0)->comment('縣市編號');
            $table->string('title', 120)->comment('區域名稱');
            $table->string('zipcode', 10)->comment('郵遞區號');
            
            $table->index('city_sn', 'idx_city_sn');
            $table->foreign('city_sn')->references('sn')->on('list_city')->onDelete('cascade');
            
            $table->comment('區域列表');
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
        
        // 重新開啟外鍵檢查
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
