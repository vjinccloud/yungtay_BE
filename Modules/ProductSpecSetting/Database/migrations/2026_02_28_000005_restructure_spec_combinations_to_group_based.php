<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 刪除舊的 spec_combination_values（存的是 value_id，不再需要）
        Schema::dropIfExists('spec_combination_values');

        // 2. 重建 spec_combinations（改為存組合名稱 + 關聯群組）
        Schema::dropIfExists('spec_combinations');

        Schema::create('spec_combinations', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('組合名稱（多語系），例如 顏色+尺寸');
            $table->integer('seq')->default(0)->comment('排序');
            $table->boolean('status')->default(true)->comment('啟用狀態');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // 3. 建立 spec_combination_groups（組合 ↔ 群組 多對多）
        Schema::create('spec_combination_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spec_combination_id');
            $table->unsignedBigInteger('spec_group_id');
            $table->timestamps();

            $table->foreign('spec_combination_id')
                ->references('id')->on('spec_combinations')
                ->onDelete('cascade');

            $table->foreign('spec_group_id')
                ->references('id')->on('spec_groups')
                ->onDelete('cascade');

            $table->unique(['spec_combination_id', 'spec_group_id'], 'combo_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spec_combination_groups');
        Schema::dropIfExists('spec_combinations');

        // 還原舊結構
        Schema::create('spec_combinations', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->string('combination_key')->unique()->nullable();
            $table->integer('seq')->default(0);
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('spec_combination_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spec_combination_id');
            $table->unsignedBigInteger('spec_group_id');
            $table->unsignedBigInteger('spec_value_id');
            $table->timestamps();

            $table->foreign('spec_combination_id')->references('id')->on('spec_combinations')->onDelete('cascade');
            $table->foreign('spec_group_id')->references('id')->on('spec_groups')->onDelete('cascade');
            $table->foreign('spec_value_id')->references('id')->on('spec_values')->onDelete('cascade');
            $table->unique(['spec_combination_id', 'spec_group_id'], 'combo_group_unique');
        });
    }
};
