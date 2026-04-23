<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_activities', function (Blueprint $table) {
            $table->id();

            // 活動基本設定
            $table->string('title')->comment('活動標題');
            $table->date('start_date')->nullable()->comment('活動開始日期');
            $table->date('end_date')->nullable()->comment('活動結束日期');
            $table->text('description')->nullable()->comment('活動描述');
            $table->string('status', 20)->default('draft')->comment('狀態：active=啟用, draft=草稿');
            $table->boolean('show_on_frontend')->default(false)->comment('前台是否顯示');
            $table->string('promo_code')->nullable()->comment('優惠代碼');

            // 條件設置
            $table->string('condition_type', 30)->default('all')->comment('條件類型：all=全部(無條件), order_total=全單達到, category=指定分類');
            $table->unsignedInteger('condition_order_total')->nullable()->comment('全單達到金額');
            $table->json('condition_category_ids')->nullable()->comment('指定分類 ID');

            // 獎勵設定
            $table->string('reward_type', 30)->default('shopping_credit')->comment('獎勵類型：shopping_credit=購物金');
            $table->unsignedInteger('reward_value')->default(0)->comment('獎勵數值');
            $table->string('credit_expiry_type', 20)->default('unlimited')->comment('購物金有效期限：unlimited=無限制, days=指定天數');
            $table->unsignedInteger('credit_expiry_days')->nullable()->comment('購物金有效天數');

            // 活動限制
            $table->string('redemption_limit_type', 30)->default('unlimited')->comment('回饋次數：unlimited=不限, once_per_member=每位會員僅限一次, site_total=全站使用次數');
            $table->unsignedInteger('redemption_site_total')->nullable()->comment('全站使用次數上限');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_activities');
    }
};
