<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 綠界物流記錄表 - 追蹤超商取貨物流狀態
     */
    public function up(): void
    {
        Schema::create('ecpay_logistics', function (Blueprint $table) {
            $table->id();
            
            // 關聯訂單
            $table->unsignedBigInteger('order_id')->nullable()->index()->comment('關聯訂單ID');
            $table->string('order_number', 50)->nullable()->index()->comment('訂單編號');
            
            // 綠界物流資訊
            $table->string('merchant_trade_no', 50)->index()->comment('綠界交易編號');
            $table->string('all_pay_logistics_id', 50)->nullable()->unique()->comment('綠界物流編號');
            $table->string('cvs_payment_no', 50)->nullable()->comment('寄貨編號');
            $table->string('cvs_validation_no', 20)->nullable()->comment('驗證碼(711專用)');
            
            // 物流類型
            $table->string('logistics_type', 20)->default('CVS')->comment('物流類型: CVS, HOME');
            $table->string('logistics_sub_type', 20)->nullable()->comment('子類型: UNIMARTC2C, FAMIC2C...');
            
            // 金額
            $table->integer('goods_amount')->default(0)->comment('商品金額');
            $table->enum('is_collection', ['Y', 'N'])->default('N')->comment('是否代收貨款');
            
            // 寄件人資訊
            $table->string('sender_name', 20)->nullable()->comment('寄件人姓名');
            $table->string('sender_phone', 20)->nullable()->comment('寄件人電話');
            
            // 收件人資訊
            $table->string('receiver_name', 20)->nullable()->comment('收件人姓名');
            $table->string('receiver_phone', 20)->nullable()->comment('收件人電話');
            
            // 門市資訊
            $table->string('receiver_store_id', 20)->nullable()->comment('收件門市代號');
            $table->string('receiver_store_name', 50)->nullable()->comment('收件門市名稱');
            $table->string('receiver_store_address', 200)->nullable()->comment('收件門市地址');
            
            // 物流狀態
            $table->string('logistics_status', 10)->nullable()->index()->comment('物流狀態碼');
            $table->string('logistics_status_name', 50)->nullable()->comment('物流狀態名稱');
            $table->integer('rtn_code')->nullable()->comment('回傳代碼');
            $table->string('rtn_msg', 200)->nullable()->comment('回傳訊息');
            
            // 狀態時間
            $table->timestamp('booking_note')->nullable()->comment('托運單建立時間');
            $table->timestamp('update_status_date')->nullable()->comment('狀態更新時間');
            
            // 原始資料
            $table->json('request_data')->nullable()->comment('請求資料');
            $table->json('response_data')->nullable()->comment('回應資料');
            $table->json('callback_data')->nullable()->comment('回調資料');
            
            // 備註
            $table->text('remark')->nullable()->comment('備註');
            
            $table->timestamps();
            
            // 複合索引
            $table->index(['logistics_status', 'created_at']);
            $table->index(['order_id', 'logistics_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecpay_logistics');
    }
};
