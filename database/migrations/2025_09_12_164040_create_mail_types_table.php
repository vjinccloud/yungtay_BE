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
        Schema::create('mail_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('收件類型名稱');
            $table->text('description')->nullable()->comment('收件類型描述');
            $table->integer('seq')->default(1)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('狀態 1=啟用 0=停用');
            $table->timestamps();
            
            $table->index('status');
            $table->index('seq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_types');
    }
};
