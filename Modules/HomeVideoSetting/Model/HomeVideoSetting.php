<?php

namespace Modules\HomeVideoSetting\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class HomeVideoSetting extends Model
{
    use HasTranslations;

    protected $table = 'home_video_settings';

    protected $fillable = [
        'title',
        'video_zh_path',
        'video_zh_name',
        'video_en_path',
        'video_en_name',
        'sort',
        'is_enabled',
    ];

    /**
     * 多語言欄位
     */
    public array $translatable = ['title'];

    protected $casts = [
        'title' => 'array',
        'is_enabled' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * 預設排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort', 'asc')->orderBy('id', 'desc');
    }

    /**
     * 只取啟用的
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
