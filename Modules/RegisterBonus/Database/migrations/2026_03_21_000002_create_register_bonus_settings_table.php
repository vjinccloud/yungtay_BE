<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('register_bonus_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('啟用狀態');
            $table->unsignedInteger('bonus_amount')->default(0)->comment('贈送數值');
            $table->string('expiry_type', 20)->default('unlimited')->comment('有效期限類型：unlimited=無限制, days=指定天數');
            $table->unsignedInteger('expiry_days')->nullable()->comment('有效天數');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('register_bonus_settings');
    }
};
