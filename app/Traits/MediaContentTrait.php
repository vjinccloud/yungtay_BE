<?php

namespace App\Traits;

use App\Models\Category;
use App\Models\ImageManagement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * MediaContentTrait
 * 
 * 影音(Drama)和節目(Program)共用的方法
 * 包含：分類關聯、圖片關聯、範圍查詢、輔助方法等
 */
trait MediaContentTrait
{
    // ==================== 關聯方法 ====================
    
    /**
     * 分類（主分類）
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 子分類
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * 圖片關聯 - 桌機版圖片
     */
    public function posterDesktop(): MorphOne
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
            ->where('image_type', 'poster_desktop');
    }

    /**
     * 圖片關聯 - 手機版圖片
     */
    public function posterMobile(): MorphOne
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
            ->where('image_type', 'poster_mobile');
    }

    /**
     * 圖片關聯 - 橫幅桌面版
     */
    public function bannerDesktop(): MorphOne
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
            ->where('image_type', 'banner_desktop');
    }

    /**
     * 圖片關聯 - 橫幅手機版
     */
    public function bannerMobile(): MorphOne
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
            ->where('image_type', 'banner_mobile');
    }

    // images() 方法已在 BaseModelTrait 中定義，無需重複

    // ==================== 範圍查詢 ====================

    /**
     * 依分類查詢
     */
    public function scopeOfCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * 依季數查詢
     */
    public function scopeOfSeason($query, $seasonNumber)
    {
        return $query->where('season_number', $seasonNumber);
    }

    /**
     * 依年份查詢
     */
    public function scopeOfYear($query, $year)
    {
        return $query->where('release_year', $year);
    }

    /**
     * 篩選條件（影音/節目共用）
     */
    public function scopeFilter($query, array $filters)
    {

        // 關鍵字搜尋（不區分大小寫）
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(title->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(title->"$.en") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(description->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(description->"$.en") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(cast->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(cast->"$.en") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(tags->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(tags->"$.en") like ?', ["%{$searchLower}%"]);
            });
        });

        // 主分類篩選
        $query->when($filters['category_id'] ?? null, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        });

        // 子分類篩選
        $query->when($filters['subcategory_id'] ?? null, function ($query, $subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        });

        // 上架日期範圍篩選
        $query->when($filters['published_start_date'] ?? null, function ($query, $startDate) {
            $query->where('published_date', '>=', $startDate);
        });

        $query->when($filters['published_end_date'] ?? null, function ($query, $endDate) {
            $query->where('published_date', '<=', $endDate);
        });

        // 年份篩選
        $query->when($filters['release_year'] ?? null, function ($query, $year) {
            $query->where('release_year', $year);
        });

        // 啟用狀態篩選
        $query->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
            $isActive = $filters['is_active'];
            // 轉換字串為 boolean
            if ($isActive === '1' || $isActive === 1 || $isActive === true) {
                $query->where('is_active', true);
            } elseif ($isActive === '0' || $isActive === 0 || $isActive === false) {
                $query->where('is_active', false);
            }
        });

        return $query;
    }

    // ==================== 輔助方法 ====================

    /**
     * 取得集數總數
     */
    public function getTotalEpisodesAttribute()
    {
        return $this->episodes()->count();
    }

    /**
     * 取得完整標題（含季數）
     */
    public function getFullTitleAttribute()
    {
        $title = $this->getTranslation('title', app()->getLocale());
        
        if ($this->season_number) {
            return $title . " 第{$this->season_number}季";
        }
        
        return $title;
    }

    /**
     * 依季數分組的集數
     */
    public function getEpisodesBySeasonAttribute()
    {
        if (!$this->relationLoaded('episodes')) {
            $this->load('episodes');
        }

        return $this->episodes->groupBy('season')->map(function ($seasonEpisodes) {
            return $seasonEpisodes->sortBy('seq')->values();
        });
    }

    /**
     * 同步主題關聯
     * 
     * @param array $themeIds
     * @return void
     */
    public function syncThemes(array $themeIds): void
    {
        $syncData = [];
        foreach ($themeIds as $index => $themeId) {
            $syncData[$themeId] = ['sort_order' => $index];
        }
        
        $this->themes()->sync($syncData);
    }
}