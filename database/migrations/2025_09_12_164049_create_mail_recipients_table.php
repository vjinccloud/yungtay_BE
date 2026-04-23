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
        Schema::create('mail_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_id')->comment('收件類型ID');
            $table->string('name', 100)->comment('收件人姓名');
            $table->string('email', 255)->comment('收件信箱');
            $table->tinyInteger('status')->default(1)->comment('狀態 1=啟用 0=停用');
            $table->unsignedBigInteger('created_by')->nullable()->comment('建立者');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新者');
            $table->timestamps();
            
            $table->foreign('type_id')->references('id')->on('mail_types')->onDelete('cascade');
            $table->index('type_id');
            $table->index('status');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_recipients');
    }
};
