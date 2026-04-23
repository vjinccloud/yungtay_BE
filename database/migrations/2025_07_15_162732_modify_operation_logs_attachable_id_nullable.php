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
        Schema::table('operation_logs', function (Blueprint $table) {
            // 修改 attachable_id 欄位，允許 null 值
            $table->unsignedBigInteger('attachable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operation_logs', function (Blueprint $table) {
            // 回復 attachable_id 欄位為必填
            $table->unsignedBigInteger('attachable_id')->nullable(false)->change();
        });
    }
};