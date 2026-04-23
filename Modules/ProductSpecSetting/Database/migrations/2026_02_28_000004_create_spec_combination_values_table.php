<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spec_combination_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spec_combination_id')->constrained('spec_combinations')->onDelete('cascade')->comment('所屬規格組合');
            $table->foreignId('spec_group_id')->constrained('spec_groups')->onDelete('cascade')->comment('規格群組');
            $table->foreignId('spec_value_id')->constrained('spec_values')->onDelete('cascade')->comment('規格值');
            $table->timestamps();

            $table->unique(['spec_combination_id', 'spec_group_id'], 'combo_group_unique');
            $table->index('spec_combination_id');
            $table->index('spec_group_id');
            $table->index('spec_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spec_combination_values');
    }
};
