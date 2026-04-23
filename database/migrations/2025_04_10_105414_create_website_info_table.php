<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_info', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable()->comment('網站標題');
            $table->string('company_name')->nullable()->comment('公司名稱');
            $table->string('tax_id')->nullable()->comment('公司統編');
            $table->string('address')->nullable()->comment('公司地址');
            $table->string('service_time')->nullable()->comment('服務時間');
            $table->string('tel',20)->nullable()->comment('公司電話');
            $table->string('fax',20)->nullable()->comment('傳真號碼');
            $table->string('line')->nullable()->comment('Line 連結');
            $table->string('fb')->nullable()->comment('FB 連結');
            $table->string('ig')->nullable()->comment('IG 連結');
            $table->string('email')->nullable()->comment('Email');
            $table->text('keyword')->nullable()->comment('網站關鍵字');
            $table->text('description')->nullable()->comment('網站描述');
            $table->text('ga_code')->nullable()->comment('Google Analysis');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('修改人員');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE website_info COMMENT = '網站資訊'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_info');
    }
};
