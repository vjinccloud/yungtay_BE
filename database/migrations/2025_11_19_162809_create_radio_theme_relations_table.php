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
        Schema::create('radio_theme_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radio_theme_id')->constrained('radio_themes')->onDelete('cascade');
            $table->foreignId('radio_id')->constrained('radios')->onDelete('cascade');
            $table->integer('sort')->default(0)->comment('在該主題中的排序');
            $table->timestamps();

            // 複合唯一約束：同一主題下的廣播不能重複
            $table->unique(['radio_theme_id', 'radio_id']);

            // 索引
            $table->index('radio_theme_id');
            $table->index('radio_id');
            $table->index('sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radio_theme_relations');
    }
};
