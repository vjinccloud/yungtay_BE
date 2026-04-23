<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 綠界門市選擇暫存表 - 暫存使用者選擇的超商門市資訊
     */
    public function up(): void
    {
        Schema::create('ecpay_cvs_stores', function (Blueprint $table) {
            $table->id();
            
            // 交易編號（用於關聯）
            $table->string('merchant_trade_no', 50)->index()->comment('綠界交易編號');
            
            // 門市資訊
            $table->string('logistics_sub_type', 20)->nullable()->comment('物流子類型');
            $table->string('cvs_store_id', 20)->nullable()->comment('門市代號');
            $table->string('cvs_store_name', 50)->nullable()->comment('門市名稱');
            $table->string('cvs_address', 200)->nullable()->comment('門市地址');
            $table->string('cvs_telephone', 20)->nullable()->comment('門市電話');
            $table->enum('cvs_outside', ['0', '1'])->default('0')->comment('是否為外島');
            
            // 額外資料
            $table->string('extra_data', 200)->nullable()->comment('自訂資料');
            
            // 是否已使用
            $table->boolean('is_used')->default(false)->index()->comment('是否已使用');
            
            $table->timestamps();
            
            // 過期時間（可用於清理舊資料）
            $table->timestamp('expires_at')->nullable()->index()->comment('過期時間');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecpay_cvs_stores');
    }
};
