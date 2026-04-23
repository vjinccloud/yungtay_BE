<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade')->comment('所屬據點');
            $table->json('name')->nullable()->comment('名稱（多語言）');
            $table->json('address')->nullable()->comment('地址（多語言）');
            $table->string('contact_person')->nullable()->comment('聯絡人');
            $table->integer('sort')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否啟用');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factories');
    }
};
