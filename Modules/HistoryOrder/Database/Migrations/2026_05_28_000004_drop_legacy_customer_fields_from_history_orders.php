<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 移除舊客戶/業務區塊 7 個欄位（已被 contact_phone / contact_email /
     * case_area / elevator_count / elevator_spec / customer_name 取代）。
     */
    public function up(): void
    {
        Schema::table('history_orders', function (Blueprint $table) {
            // 連同 sales_name 一起刪，原本 create migration 上的 index 會被一併移除
            $table->dropIndex(['sales_name']);
        });

        Schema::table('history_orders', function (Blueprint $table) {
            $table->dropColumn([
                'project_name',
                'construction_location',
                'customer_contact_name',
                'customer_contact_email',
                'sales_name',
                'sales_email',
                'sales_phone',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('history_orders', function (Blueprint $table) {
            $table->string('project_name', 100)->nullable()->after('customer_name')
                  ->comment('專案名稱');
            $table->string('construction_location', 255)->nullable()->after('project_name')
                  ->comment('施工地點');
            $table->string('customer_contact_name', 50)->nullable()->after('construction_location')
                  ->comment('客戶窗口姓名');
            $table->string('customer_contact_email', 100)->nullable()->after('customer_contact_name')
                  ->comment('客戶窗口信箱');
            $table->string('sales_name', 50)->nullable()->after('series_model')
                  ->comment('業務姓名');
            $table->string('sales_email', 100)->nullable()->after('sales_name')
                  ->comment('業務人員信箱');
            $table->string('sales_phone', 30)->nullable()->after('sales_email')
                  ->comment('業務連絡電話');

            $table->index('sales_name');
        });
    }
};
