<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 30)->unique()->comment('訂單編號');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('會員ID');

            // 訂單狀態
            $table->string('status', 30)->default('pending')->index()
                  ->comment('pending/paid/awaiting_shipment/shipped/completed/cancelled/refund_requested/refunded');

            // 買家資訊
            $table->string('buyer_name', 50)->comment('買家姓名');
            $table->string('buyer_phone', 20)->comment('買家電話');
            $table->string('buyer_email', 100)->nullable()->comment('買家信箱');
            $table->text('buyer_note')->nullable()->comment('買家備註');
            $table->text('admin_note')->nullable()->comment('管理員備註');

            // 付款
            $table->string('payment_method', 30)->comment('credit_card/atm/cvs/cod');
            $table->string('ecpay_merchant_trade_no', 50)->nullable()->index()->comment('綠界交易編號');
            $table->string('ecpay_trade_no', 50)->nullable()->comment('綠界內部編號');
            $table->timestamp('paid_at')->nullable()->comment('付款時間');

            // 物流
            $table->string('shipping_method', 30)->comment('cvs_711/cvs_family/cvs_hilife/home');
            $table->string('receiver_name', 50)->comment('收件人姓名');
            $table->string('receiver_phone', 20)->comment('收件人電話');
            $table->string('receiver_address', 255)->nullable()->comment('收件地址（宅配用）');
            $table->string('receiver_store_id', 20)->nullable()->comment('收件門市代號（超取用）');
            $table->string('receiver_store_name', 50)->nullable()->comment('收件門市名稱');
            $table->string('logistics_id', 50)->nullable()->comment('物流訂單編號');
            $table->string('logistics_status', 20)->nullable()->comment('物流狀態碼');
            $table->string('logistics_status_name', 50)->nullable()->comment('物流狀態名稱');
            $table->timestamp('shipped_at')->nullable()->comment('出貨時間');
            $table->timestamp('completed_at')->nullable()->comment('完成時間');

            // 金額
            $table->integer('subtotal')->default(0)->comment('商品小計');
            $table->integer('shipping_fee')->default(0)->comment('運費');
            $table->integer('discount')->default(0)->comment('折扣');
            $table->integer('total_amount')->default(0)->comment('訂單總額');

            // 發票
            $table->string('invoice_type', 30)->nullable()->comment('發票類型');
            $table->string('invoice_no', 20)->nullable()->comment('發票號碼');
            $table->string('invoice_carrier_num', 64)->nullable()->comment('載具號碼');
            $table->string('invoice_status', 20)->nullable()->comment('發票狀態');

            // 取消/退款
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancelled_reason')->nullable();

            $table->timestamps();

            // 複合索引
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
