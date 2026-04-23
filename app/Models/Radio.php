<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Radio extends Model
{
    use HasTranslations;
    use BaseModelTrait;

    protected $fillable = [
        'title',
        'description',
        'media_name',
        'audio_url',
        'category_id',
        'subcategory_id',
        'year',
        'season',
        'publish_date',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by'
    ];

    public $translatable = [
        'title',
        'description',
        'media_name'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'publish_date' => 'date',
        'sort_order' => 'integer',
        'year' => 'integer'
    ];

    protected $with = ['created_user', 'updated_user', 'image', 'bannerDesktop', 'bannerMobile'];

    // ==================== 關聯 ====================

    /**
     * 廣播分類
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 廣播子分類
     */
    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * 廣播集數
     */
    public function episodes()
    {
        return $this->hasMany(RadioEpisode::class)->orderBy('sort_order');
    }

    /**
     * 取得此廣播的主題
     */
    public function themes()
    {
        return $this->belongsToMany(RadioTheme::class, 'radio_theme_relations', 'radio_id', 'radio_theme_id')
                    ->withPivot('sort')
                    ->withTimestamps()
                    ->orderBy('radio_theme_relations.sort');
    }

    /**
     * 建立者
     */
    public function created_user()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    /**
     * 更新者
     */
    public function updated_user()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }


    /**
     * 所有圖片（多型關聯）
     */
    public function image()
    {
        return $this->morphOne(ImageManagement::class, 'attachable');
    }

    /**
     * Banner 圖片關聯 - 桌機版
     */
    public function bannerDesktop()
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
            ->where('image_type', 'banner_desktop');
    }

    /**
     * Banner 圖片關聯 - 手機版
     */
    public function bannerMobile()
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
            ->where('image_type', 'banner_mobile');
    }

    // ==================== 屬性存取器 ====================

    /**
     * 操作紀錄標題
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value // 先看有沒有手動覆寫
                ?: '廣播：' . $this->getTranslation('title', 'zh_TW'),
            set: fn (string $value) => $value,
        );
    }

    // ==================== Scopes ====================

    /**
     * 搜尋範圍：可以過濾標題關鍵字、分類、子分類、年份、啟用狀態等（不區分大小寫）
     */
    public function scopeFilter($query, array $filters)
    {
        // 搜尋邏輯（標題和媒體名稱，不區分大小寫）
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(title->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(title->"$.en") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(media_name->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(media_name->"$.en") like ?', ["%{$searchLower}%"]);
            });
        });

        // 分類篩選
        $query->when($filters['category_id'] ?? null, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        });

        // 子分類篩選（支援單一或多個子分類）
        $query->when($filters['subcategory_id'] ?? null, function ($query, $subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        });

        // 子分類陣列篩選（用於前台 AJAX 篩選）
        $query->when(!empty($filters['subcategories']), function ($query) use ($filters) {
            $query->whereIn('subcategory_id', $filters['subcategories']);
        });

        // 年份篩選（支援多選與 before_2015）
        $query->when(!empty($filters['years']), function ($query) use ($filters) {
            $years = collect($filters['years']);

            // 處理 "2015 以前" 的特殊情況
            if ($years->contains('before_2015')) {
                $normalYears = $years->filter(function ($year) {
                    return $year !== 'before_2015';
                })->toArray();

                $query->where(function ($q) use ($normalYears) {
                    if (!empty($normalYears)) {
                        $q->whereIn('year', $normalYears);
                    }
                    $q->orWhere('year', '<', 2015);
                });
            } else {
                // 沒有 before_2015 時，直接 whereIn
                $query->whereIn('year', $years->toArray());
            }
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
}
