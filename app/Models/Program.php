<?php
// app/Models/Program.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use App\Traits\MediaContentTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Program extends Model
{
    use HasTranslations;
    use BaseModelTrait;
    use MediaContentTrait;

    protected $fillable = [
        'title',
        'description',
        'cast',
        'crew',
        'tags',
        'other_info',
        'category_id',
        'subcategory_id',
        'season_number',
        'release_year',
        'is_active',
        'published_date',
        'created_by',
        'updated_by'
    ];

    public $translatable = [
        'title',
        'description',
        'cast',
        'crew',
        'tags',
        'other_info'
    ];


    protected $casts = [
        'is_active' => 'boolean',
        'season_number' => 'integer',
        'release_year' => 'integer',
        'published_date' => 'date',
    ];

    protected $with = ['posterDesktop', 'posterMobile', 'bannerDesktop', 'bannerMobile'];

    // ==================== 關聯 ====================

    /**
     * 節目影音集數
     */
    public function episodes()
    {
        return $this->hasMany(ProgramEpisode::class)->orderBy('seq');
    }


    // 圖片關聯已移至 MediaContentTrait



    // ==================== 範圍查詢 ====================
    // 範圍查詢已移至 MediaContentTrait

    // ==================== 輔助方法 ====================
    // 輔助方法已移至 MediaContentTrait

    /**
     * 操作紀錄標題
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value // 先看有沒有手動覆寫
            ?: '節目 ：'.$this->getTranslation('title', 'zh_TW'),
            set: fn (string $value) => $value,
        );
    }

    /**
     * 取得此節目的主題
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(ProgramTheme::class, 'program_theme_relations', 'program_id', 'theme_id')
                    ->withPivot('sort_order')
                    ->withTimestamps()
                    ->orderBy('program_theme_relations.sort_order');
    }

    // syncThemes 方法已移至 MediaContentTrait
}