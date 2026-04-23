<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_orders', function (Blueprint $table) {
            // 車廂規格（JSON）
            $table->json('cabin_specs')->nullable()->after('series_model')
                  ->comment('車廂規格：天井、門板、側板、地板、操作盤、扶手、飾條');

            // 出入口規格（JSON）
            $table->json('entrance_specs')->nullable()->after('cabin_specs')
                  ->comment('出入口規格：門板、門框、門柱、地板、操作盤');

            // 電梯渲染圖
            $table->string('elevator_image', 500)->nullable()->after('entrance_specs')
                  ->comment('電梯渲染圖路徑');

            // 客戶訂車資料
            $table->string('project_name', 100)->nullable()->after('customer_name')
                  ->comment('專案名稱');
            $table->string('construction_location', 255)->nullable()->after('project_name')
                  ->comment('施工地點');
            $table->string('customer_contact_name', 50)->nullable()->after('construction_location')
                  ->comment('客戶窗口姓名');
            $table->string('customer_contact_email', 100)->nullable()->after('customer_contact_name')
                  ->comment('客戶窗口信箱');
            $table->string('sales_email', 100)->nullable()->after('sales_name')
                  ->comment('業務人員信箱');
            $table->string('sales_phone', 30)->nullable()->after('sales_email')
                  ->comment('業務連絡電話');
        });
    }

    public function down(): void
    {
        Schema::table('history_orders', function (Blueprint $table) {
            $table->dropColumn([
                'cabin_specs',
                'entrance_specs',
                'elevator_image',
                'project_name',
                'construction_location',
                'customer_contact_name',
                'customer_contact_email',
                'sales_email',
                'sales_phone',
            ]);
        });
    }
};
