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
        // 先取得現有資料
        $websiteInfo = DB::table('website_info')->first();
        
        // 暫存舊資料
        $oldData = [];
        if ($websiteInfo) {
            $oldData = [
                'title' => $websiteInfo->title,
                'description' => $websiteInfo->description,
                'keyword' => $websiteInfo->keyword,
                'company_name' => $websiteInfo->company_name,
                'address' => $websiteInfo->address
            ];
        }
        
        // 先將欄位值設為 null，避免轉換錯誤
        if ($websiteInfo) {
            DB::table('website_info')->where('id', $websiteInfo->id)->update([
                'title' => null,
                'description' => null,
                'keyword' => null,
                'company_name' => null,
                'address' => null
            ]);
        }
        
        // 修改欄位為 JSON 類型
        Schema::table('website_info', function (Blueprint $table) {
            $table->json('title')->nullable()->comment('網站標題（多語言）')->change();
            $table->json('description')->nullable()->comment('網站描述（多語言）')->change();
            $table->json('keyword')->nullable()->comment('SEO關鍵字（多語言）')->change();
            $table->json('company_name')->nullable()->comment('公司名稱（多語言）')->change();
            $table->json('address')->nullable()->comment('地址（多語言）')->change();
        });
        
        // 轉換現有資料為多語言格式
        if ($websiteInfo && !empty($oldData)) {
            DB::table('website_info')->where('id', $websiteInfo->id)->update([
                'title' => json_encode([
                    'zh_TW' => $oldData['title'] ?? '',
                    'en' => ''
                ]),
                'description' => json_encode([
                    'zh_TW' => $oldData['description'] ?? '',
                    'en' => ''
                ]),
                'keyword' => json_encode([
                    'zh_TW' => $oldData['keyword'] ?? '',
                    'en' => ''
                ]),
                'company_name' => json_encode([
                    'zh_TW' => $oldData['company_name'] ?? '',
                    'en' => ''
                ]),
                'address' => json_encode([
                    'zh_TW' => $oldData['address'] ?? '',
                    'en' => ''
                ])
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 先取得資料
        $websiteInfo = DB::table('website_info')->first();
        
        // 轉換回單語言格式
        if ($websiteInfo) {
            $title = json_decode($websiteInfo->title, true);
            $description = json_decode($websiteInfo->description, true);
            $keyword = json_decode($websiteInfo->keyword, true);
            $company_name = json_decode($websiteInfo->company_name, true);
            $address = json_decode($websiteInfo->address, true);
            
            DB::table('website_info')->where('id', $websiteInfo->id)->update([
                'title' => $title['zh_TW'] ?? '',
                'description' => $description['zh_TW'] ?? '',
                'keyword' => $keyword['zh_TW'] ?? '',
                'company_name' => $company_name['zh_TW'] ?? '',
                'address' => $address['zh_TW'] ?? ''
            ]);
        }
        
        Schema::table('website_info', function (Blueprint $table) {
            // 改回 string 類型
            $table->string('title')->nullable()->comment('網站標題')->change();
            $table->text('description')->nullable()->comment('網站描述')->change();
            $table->text('keyword')->nullable()->comment('網站關鍵字')->change();
            $table->string('company_name')->nullable()->comment('公司名稱')->change();
            $table->string('address')->nullable()->comment('公司地址')->change();
        });
    }
};
