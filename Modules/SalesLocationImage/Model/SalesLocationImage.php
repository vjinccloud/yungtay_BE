<?php

namespace Modules\SalesLocationImage\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * SalesLocationImage 銷售據點圖片管理 - Model
 */
class SalesLocationImage extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'sales_location_images';

    protected $fillable = [
        'title',
        'sort',
        'is_enabled',
        'created_by',
        'updated_by'
    ];

    public $translatable = ['title'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort' => 'integer',
    ];

    protected $with = ['imageZh', 'imageEn'];

    /**
     * 中文版圖片
     */
    public function imageZh()
    {
        return $this->morphImage('image_zh');
    }

    /**
     * 英文版圖片
     */
    public function imageEn()
    {
        return $this->morphImage('image_en');
    }

    /**
     * 排序 scope
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 啟用狀態 scope
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
