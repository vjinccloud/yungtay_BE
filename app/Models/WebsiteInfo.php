<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Translatable\HasTranslations;

//網站資訊
class WebsiteInfo extends Model
{
    use HasFactory;
    use BaseModelTrait;
    use HasTranslations;

    protected $table = 'website_info';
    protected $fillable = [
        'title', 'description', 'keyword', 'company_name', 'tax_id', 'address', 
        'service_time', 'tel', 'fax', 'line', 'fb', 'ig', 'youtube', 'email', 
        'app_google_play', 'app_apple_store', 'ga_code'
    ];

    /**
     * 多語言欄位
     */
    public $translatable = [
        'title',        // 網站標題
        'description',  // 網站描述
        'keyword',      // SEO 關鍵字
        'company_name', // 公司名稱
        'address'       // 公司地址
    ];

    protected $with = ['favicon','updated_user', 'websiteIcon'];
    //更新人員
    public function updated_user()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }
    public function favicon()
    {
        return $this->morphMany(ImageManagement::class, 'attachable')
            ->where('image_type', 'icon');
    }
    
    // 網站圖示關聯
    public function websiteIcon()
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
            ->where('image_type', 'website_icon');
    }

    /**
     * 操作紀錄標題
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) =>  $value ?? '網站基本資料',
        );
    }
}