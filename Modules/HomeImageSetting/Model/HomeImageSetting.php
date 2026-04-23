<?php

namespace Modules\HomeImageSetting\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * HomeImageSetting 首頁圖片設定 - Model
 */
class HomeImageSetting extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'home_image_settings';

    protected $fillable = [
        'title',
        'created_by',
        'updated_by'
    ];

    public $translatable = ['title'];

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
}
