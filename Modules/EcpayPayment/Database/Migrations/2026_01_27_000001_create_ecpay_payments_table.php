<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 綠界付款記錄表 - 追蹤每筆付款交易狀態
     */
    public function up(): void
    {
        Schema::create('ecpay_payments', function (Blueprint $table) {
            $table->id();
            
            // 綠界交易資訊
            $table->string('merchant_trade_no', 50)->unique()->comment('特店交易編號');
            $table->string('trade_no', 50)->nullable()->comment('綠界內部交易編號');
            
            // 付款資訊
            $table->integer('total_amount')->default(0)->comment('付款金額');
            $table->string('payment_type', 20)->nullable()->comment('付款方式: Credit, ATM, CVS...');
            $table->string('payment_type_charge_fee', 20)->nullable()->comment('付款方式手續費負擔方');
            
            // 付款狀態
            $table->string('trade_status', 20)->default('pending')->index()
                  ->comment('交易狀態: pending, processing, paid, failed, refunded');
            $table->integer('rtn_code')->nullable()->comment('綠界回傳代碼');
            $table->string('rtn_msg', 200)->nullable()->comment('綠界回傳訊息');
            
            // 時間記錄
            $table->timestamp('trade_date')->nullable()->comment('交易時間');
            $table->timestamp('payment_date')->nullable()->comment('付款時間');
            
            // 會員資訊
            $table->string('member_id', 100)->nullable()->index()->comment('會員ID');
            $table->string('member_email', 100)->nullable()->comment('會員Email');
            $table->string('member_phone', 20)->nullable()->comment('會員手機');
            
            // Token 資訊
            $table->string('pay_token', 100)->nullable()->comment('付款Token');
            
            // 原始資料（除錯用）
            $table->json('request_data')->nullable()->comment('請求資料');
            $table->json('response_data')->nullable()->comment('回應資料');
            $table->json('notify_data')->nullable()->comment('背景通知資料');
            
            // 備註
            $table->text('remark')->nullable()->comment('備註');
            
            $table->timestamps();
            
            // 複合索引
            $table->index(['trade_status', 'created_at']);
            $table->index(['member_id', 'trade_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecpay_payments');
    }
};
