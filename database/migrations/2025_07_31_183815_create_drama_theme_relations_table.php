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
        Schema::create('drama_theme_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drama_id')->nullable()->comment('影音ID，允許為null');
            $table->foreignId('theme_id')->constrained('drama_themes')->onDelete('cascade');
            $table->integer('sort_order')->default(0)->comment('排序順序');
            $table->timestamps();
            
            // 複合索引
            $table->index(['theme_id', 'sort_order']);
            $table->index(['drama_id', 'theme_id']);
            
            // 防止重複關聯
            $table->unique(['drama_id', 'theme_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drama_theme_relations');
    }
};