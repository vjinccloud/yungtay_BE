<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            // 將單一欄位改為多語言 JSON 欄位
            $table->json('image_zh')->nullable()->after('address')->comment('主圖（中文）');
            $table->json('image_en')->nullable()->after('image_zh')->comment('主圖（英文）');
            $table->json('logo_zh')->nullable()->after('image_en')->comment('Logo（中文）');
            $table->json('logo_en')->nullable()->after('logo_zh')->comment('Logo（英文）');
            $table->json('images_zh')->nullable()->after('logo_en')->comment('多張圖片（中文）');
            $table->json('images_en')->nullable()->after('images_zh')->comment('多張圖片（英文）');
            $table->json('visit_video_zh')->nullable()->after('images_en')->comment('訪廠影片（中文）');
            $table->json('visit_video_en')->nullable()->after('visit_video_zh')->comment('訪廠影片（英文）');
            $table->json('video_360_zh')->nullable()->after('visit_video_en')->comment('360影片（中文）');
            $table->json('video_360_en')->nullable()->after('video_360_zh')->comment('360影片（英文）');
        });

        // 移除舊欄位
        Schema::table('factories', function (Blueprint $table) {
            $table->dropColumn(['image', 'logo', 'images', 'visit_video', 'video_360']);
        });
    }

    public function down(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->dropColumn([
                'image_zh', 'image_en', 
                'logo_zh', 'logo_en',
                'images_zh', 'images_en',
                'visit_video_zh', 'visit_video_en',
                'video_360_zh', 'video_360_en'
            ]);
            
            $table->string('image')->nullable()->after('address');
            $table->string('logo')->nullable()->after('image');
            $table->json('images')->nullable()->after('logo');
            $table->string('visit_video')->nullable()->after('images');
            $table->string('video_360')->nullable()->after('visit_video');
        });
    }
};
