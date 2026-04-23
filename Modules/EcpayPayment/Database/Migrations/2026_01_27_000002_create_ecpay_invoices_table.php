<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 綠界電子發票記錄表 - 獨立管理發票生命週期
     */
    public function up(): void
    {
        Schema::create('ecpay_invoices', function (Blueprint $table) {
            $table->id();
            
            // 關聯付款記錄
            $table->unsignedBigInteger('ecpay_payment_id')->index()
                  ->comment('關聯的付款記錄ID');
            
            // 發票基本資訊
            $table->string('invoice_no', 20)->nullable()->unique()->comment('發票號碼');
            $table->string('random_number', 4)->nullable()->comment('發票隨機碼');
            $table->timestamp('invoice_date')->nullable()->comment('發票開立日期');
            $table->string('status', 20)->default('pending')->index()
                  ->comment('發票狀態: pending=待開立, issued=已開立, void=已作廢, allowance=已折讓');
            
            // 發票類型設定
            $table->tinyInteger('type')->default(1)
                  ->comment('發票類型: 1=個人, 2=公司');
            $table->string('carrier_type', 10)->nullable()
                  ->comment('載具類型: 空白=無載具(紙本), 1=綠界電子發票載具, 2=手機條碼, 3=自然人憑證');
            $table->string('carrier_num', 64)->nullable()
                  ->comment('載具編號');
            
            // 公司發票資訊
            $table->string('company_name', 100)->nullable()->comment('公司名稱');
            $table->string('tax_id', 8)->nullable()->comment('統一編號');
            
            // 捐贈發票
            $table->boolean('donation')->default(false)->comment('是否捐贈發票');
            $table->string('love_code', 10)->nullable()->comment('愛心碼');
            
            // 發票金額
            $table->integer('sales_amount')->default(0)->comment('銷售額(未稅)');
            $table->integer('tax_amount')->default(0)->comment('稅額');
            $table->integer('total_amount')->default(0)->comment('總金額(含稅)');
            
            // 發票品項（JSON 格式儲存多筆品項）
            $table->json('items')->nullable()->comment('發票品項明細');
            
            // 紙本發票收件資訊
            $table->string('print_name', 50)->nullable()->comment('收件人姓名');
            $table->string('print_address', 200)->nullable()->comment('收件地址');
            $table->string('print_phone', 20)->nullable()->comment('收件電話');
            
            // 買受人資訊
            $table->string('buyer_name', 100)->nullable()->comment('買受人名稱');
            $table->string('buyer_email', 100)->nullable()->comment('買受人Email');
            $table->string('buyer_phone', 20)->nullable()->comment('買受人電話');
            
            // 綠界回傳資訊
            $table->string('relate_number', 30)->nullable()->comment('綠界發票關聯編號');
            $table->string('rtn_code', 10)->nullable()->comment('綠界回傳代碼');
            $table->string('rtn_msg', 200)->nullable()->comment('綠界回傳訊息');
            
            // 作廢/折讓相關
            $table->timestamp('void_date')->nullable()->comment('作廢日期');
            $table->string('void_reason', 200)->nullable()->comment('作廢原因');
            $table->integer('allowance_amount')->nullable()->comment('折讓金額');
            $table->timestamp('allowance_date')->nullable()->comment('折讓日期');
            
            // 原始資料
            $table->json('request_data')->nullable()->comment('開立請求資料');
            $table->json('response_data')->nullable()->comment('開立回應資料');
            
            // 備註
            $table->text('remark')->nullable()->comment('備註');
            
            $table->timestamps();
            
            // 索引
            $table->index(['status', 'created_at']);
            $table->index(['ecpay_payment_id', 'status']);
            
            // 外鍵約束
            $table->foreign('ecpay_payment_id')
                  ->references('id')
                  ->on('ecpay_payments')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecpay_invoices');
    }
};
