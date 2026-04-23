<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FrontMenuSetting 前台選單管理 - 資料表遷移
 * 
 * 層級式選單結構（支援無限層級）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('front_menus', function (Blueprint $table) {
            $table->id();

            // 父層選單 ID（0 = 頂層）
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父層選單 ID');

            // 選單名稱（多語言 JSON）
            $table->json('title')->nullable()->comment('選單名稱（多語言）');

            // 層級（0 = 頂層, 1 = 第一子層...）
            $table->unsignedTinyInteger('level')->default(0)->comment('層級深度');

            // 連結類型：url = 外部連結, route = 內部路由, page = 頁面, none = 無連結（純分類）
            $table->string('link_type', 20)->default('none')->comment('連結類型');

            // 連結網址（外部連結或內部路徑）
            $table->string('link_url', 500)->nullable()->comment('連結網址');

            // 開啟方式：_self = 同分頁, _blank = 新分頁
            $table->string('link_target', 10)->default('_self')->comment('連結開啟方式');

            // 圖標（CSS class，如 fa fa-home）
            $table->string('icon', 100)->nullable()->comment('圖標 class');

            // 排序（數字越小越前面）
            $table->unsignedSmallInteger('seq')->default(0)->comment('排序');

            // 啟用狀態
            $table->boolean('status')->default(true)->comment('啟用狀態');

            // 建立者/更新者
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // 索引
            $table->index('parent_id');
            $table->index('status');
            $table->index('seq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_menus');
    }
};
