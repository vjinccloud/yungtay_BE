<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->json('title')->nullable()->after('name')->comment('標題（多語言）');
            $table->string('image')->nullable()->after('address')->comment('主圖');
            $table->string('logo')->nullable()->after('image')->comment('Logo圖片');
            $table->json('images')->nullable()->after('logo')->comment('多張圖片（最多10張）');
            $table->string('visit_video')->nullable()->after('images')->comment('訪廠影片');
            $table->string('video_360')->nullable()->after('visit_video')->comment('360影片');
        });
    }

    public function down(): void
    {
        Schema::table('factories', function (Blueprint $table) {
            $table->dropColumn(['title', 'image', 'logo', 'images', 'visit_video', 'video_360']);
        });
    }
};
