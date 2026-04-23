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
        Schema::table('website_info', function (Blueprint $table) {
            $table->string('youtube')->nullable()->comment('YouTube 連結');
            $table->string('app_google_play')->nullable()->comment('Google Play 下載連結');
            $table->string('app_apple_store')->nullable()->comment('Apple Store 下載連結');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_info', function (Blueprint $table) {
            $table->dropColumn(['youtube', 'app_google_play', 'app_apple_store']);
        });
    }
};
