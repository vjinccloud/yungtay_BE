<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 新增客戶訂單資料區塊的新欄位：
     *   contact_phone / contact_email / case_area / elevator_count / elevator_spec
     * 舊欄位（project_name 等）保留，舊資料不搬。
     */
    public function up(): void
    {
        Schema::table('history_orders', function (Blueprint $table) {
            $table->string('contact_phone', 30)->nullable()->after('customer_name')
                  ->comment('聯絡電話');
            $table->string('contact_email', 100)->nullable()->after('contact_phone')
                  ->comment('聯絡信箱');
            $table->string('case_area', 100)->nullable()->after('contact_email')
                  ->comment('案件地區');
            $table->unsignedInteger('elevator_count')->nullable()->after('case_area')
                  ->comment('電梯台數');
            $table->string('elevator_spec', 100)->nullable()->after('elevator_count')
                  ->comment('電梯規格（100 字內）');
        });
    }

    public function down(): void
    {
        Schema::table('history_orders', function (Blueprint $table) {
            $table->dropColumn([
                'contact_phone',
                'contact_email',
                'case_area',
                'elevator_count',
                'elevator_spec',
            ]);
        });
    }
};
