<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Factory extends Model
{
    use HasTranslations;

    protected $table = 'factories';

    protected $fillable = [
        'region_id',
        'name',
        'title',
        'address',
        'country_name',
        'established_date',
        'image_zh',
        'image_en',
        'logo_zh',
        'logo_en',
        'images_zh',
        'images_en',
        'visit_video_zh',
        'visit_video_en',
        'video_360_zh',
        'video_360_en',
        'contact_person',
        'sort',
        'is_enabled',
    ];

    public $translatable = ['name', 'title', 'address', 'country_name'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'images_zh' => 'array',
        'images_en' => 'array',
    ];

    /**
     * 排序 Scope
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort', 'asc')->orderBy('id', 'desc');
    }

    /**
     * 啟用 Scope
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 關聯：所屬據點
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * 關聯：產品服務（多對多）
     */
    public function productServices()
    {
        return $this->belongsToMany(ProductService::class, 'factory_product_service');
    }
}
