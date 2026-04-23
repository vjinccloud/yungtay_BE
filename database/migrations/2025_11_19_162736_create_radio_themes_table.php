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
        Schema::create('radio_themes', function (Blueprint $table) {
            $table->id();
            $table->json('title')->comment('主題名稱（多語系）');
            $table->string('poster_desktop', 255)->nullable()->comment('桌面版橫幅');
            $table->string('poster_mobile', 255)->nullable()->comment('手機版橫幅');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('is_active')->default(1)->comment('是否啟用');
            $table->timestamps();

            // 索引
            $table->index('is_active');
            $table->index('sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radio_themes');
    }
};
