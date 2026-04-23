<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 移除 JSON 約束並改為 string 類型
        // 需要先刪除再重建欄位（因為 MariaDB 不支援直接 change json to string）
        
        Schema::table('factories', function (Blueprint $table) {
            // 先刪除錯誤的 json 欄位
            $table->dropColumn([
                'image_zh', 'image_en',
                'logo_zh', 'logo_en',
                'visit_video_zh', 'visit_video_en',
                'video_360_zh', 'video_360_en',
            ]);
        });

        Schema::table('factories', function (Blueprint $table) {
            // 重新建立正確類型的欄位
            $table->string('image_zh', 500)->nullable()->after('address')->comment('主圖（中文）');
            $table->string('image_en', 500)->nullable()->after('image_zh')->comment('主圖（英文）');
            $table->string('logo_zh', 500)->nullable()->after('image_en')->comment('Logo（中文）');
            $table->string('logo_en', 500)->nullable()->after('logo_zh')->comment('Logo（英文）');
            // images_zh 和 images_en 保持 json（它們是陣列）
            $table->string('visit_video_zh', 500)->nullable()->after('images_en')->comment('訪廠影片（中文）');
            $table->string('visit_video_en', 500)->nullable()->after('visit_video_zh')->comment('訪廠影片（英文）');
            $table->string('video_360_zh', 500)->nullable()->after('visit_video_en')->comment('360影片（中文）');
            $table->string('video_360_en', 500)->nullable()->after('video_360_zh')->comment('360影片（英文）');
        });
    }

    public function down(): void
    {
        // Reverse is not critical for this fix
    }
};
