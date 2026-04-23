<?php

namespace App\Models;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Banner extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $fillable = [
        'title',
        'subtitle_1',
        'subtitle_2',
        'url',
        'tags',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by'
    ];

    public $translatable = ['title', 'subtitle_1', 'subtitle_2', 'tags'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $with = ['desktopImage', 'mobileImage'];

    /**
     * 操作紀錄標題
     * 使用 BaseModelTrait 的 getDefaultEventTitle 方法
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?: $this->getDefaultEventTitle(),
            set: fn (string $value) => $value,
        );
    }

    /**
     * 搜尋範圍：可以過濾標題關鍵字、啟用狀態等
     */
    public function scopeFilter($query, array $filters)
    {
        // 搜尋邏輯
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title->zh_TW', 'like', "%{$search}%")
                  ->orWhere('title->en', 'like', "%{$search}%")
                  ->orWhere('subtitle_1->zh_TW', 'like', "%{$search}%")
                  ->orWhere('subtitle_1->en', 'like', "%{$search}%")
                  ->orWhere('subtitle_2->zh_TW', 'like', "%{$search}%")
                  ->orWhere('subtitle_2->en', 'like', "%{$search}%");
            });
        });

        return $query;
    }

    /**
     * 前台查詢範圍
     */
    public function scopeFrontend($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * 桌機版圖片關聯
     * 使用 BaseModelTrait 的 morphImage 方法
     */
    public function desktopImage()
    {
        return $this->morphImage('desktop_image');
    }

    /**
     * 手機版圖片關聯
     * 使用 BaseModelTrait 的 morphImage 方法
     */
    public function mobileImage()
    {
        return $this->morphImage('mobile_image');
    }

    // images() 方法已由 BaseModelTrait 提供
    // scopeActive() 方法已由 BaseModelTrait 提供
    // creator() 和 updater() 關聯已由 BaseModelTrait 提供
}