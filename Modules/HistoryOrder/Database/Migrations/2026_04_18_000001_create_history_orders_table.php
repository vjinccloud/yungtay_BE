<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_name', 100)->comment('訂單名稱（如：日立永大_客戶姓名）');
            $table->string('customer_name', 50)->comment('客戶姓名');
            $table->string('series_model', 50)->nullable()->comment('系列型號（如：EAS）');
            $table->string('sales_name', 50)->comment('業務姓名');
            $table->text('note')->nullable()->comment('備註');
            $table->timestamps();

            $table->index('customer_name');
            $table->index('series_model');
            $table->index('sales_name');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_orders');
    }
};
