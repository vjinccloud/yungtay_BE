<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spec_groups', function (Blueprint $table) {
            $table->id();
            $table->json('name')->nullable()->comment('規格群組名稱（多語言）');
            $table->unsignedSmallInteger('seq')->default(0)->comment('排序');
            $table->boolean('status')->default(true)->comment('啟用狀態');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('seq');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spec_groups');
    }
};
