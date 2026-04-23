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
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->nullable()->comment('操作人員');
            $table->foreign('created_by')->references('id')->on('admin_users')->onDelete('cascade');
            $table->string('action_type')->nullable()->comment('log類型');
            $table->string('message')->nullable()->comment('說明');
            $table->ipAddress('ip_address')->nullable();
            $table->string('attachable_type', 255)->comment('来源表');
            $table->bigInteger('attachable_id')->comment('来源表 ID');
            $table->json('details')->nullable()->comment('紀錄來源表資料');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE operation_logs COMMENT = '操作 Log'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
    }
};
