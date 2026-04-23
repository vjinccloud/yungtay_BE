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
        Schema::table('view_statistics', function (Blueprint $table) {
            $table->unsignedBigInteger('member_views')->default(0)->after('unique_views')->comment('會員觀看次數');
            $table->unsignedBigInteger('guest_views')->default(0)->after('member_views')->comment('訪客觀看次數');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('view_statistics', function (Blueprint $table) {
            $table->dropColumn(['member_views', 'guest_views']);
        });
    }
};
