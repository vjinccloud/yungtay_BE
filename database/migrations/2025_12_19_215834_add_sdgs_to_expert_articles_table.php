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
        Schema::table('expert_articles', function (Blueprint $table) {
            $table->json('sdgs')->nullable()->after('tags')->comment('SDGs 標籤（JSON 陣列，至多5個）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expert_articles', function (Blueprint $table) {
            $table->dropColumn('sdgs');
        });
    }
};
