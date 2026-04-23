<?php

namespace App\Models;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Article extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $translatable = ['title', 'author', 'location', 'content', 'tags'];

    protected $fillable = [
        'title',
        'category_id',
        'publish_date',
        'author',
        'location',
        'content',
        'tags',
        'is_active',
        'created_by',
        'updated_by',
        // RSS 來源追蹤欄位
        'source_provider',
        'source_guid_hash',
        'source_link',
        'source_published_at',
        'source_modified_at',
        'source_comments_count',
    ];

    protected $with = ['creator', 'updater', 'images', 'category', 'image_thumbnail', 'image_normal'];

    protected $casts = [
        'publish_date' => 'date',
        'is_active' => 'boolean',
        'source_published_at' => 'datetime',
        'source_modified_at' => 'datetime',
    ];

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

    // 關聯
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 圖片關聯（單一圖片）
    public function image()
    {
        return $this->morphOne(\App\Models\ImageManagement::class, 'attachable');
    }

    // 縮圖關聯
    public function image_thumbnail()
    {
        return $this->morphOne(\App\Models\ImageManagement::class, 'attachable')
                    ->where('image_type', 'image_thumbnail');
    }

    // 一般圖片關聯
    public function image_normal()
    {
        return $this->morphOne(\App\Models\ImageManagement::class, 'attachable')
                    ->where('image_type', 'image_normal');
    }

    // 篩選 scope（不區分大小寫）
    public function scopeFilter($query, array $filters)
    {
        // 搜尋邏輯（不區分大小寫）
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(title->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(title->"$.en") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(content->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(content->"$.en") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(author->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(author->"$.en") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(location->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(location->"$.en") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(tags->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(tags->"$.en") like ?', ["%{$searchLower}%"]);
            });
        });

        // 分類篩選
        $query->when($filters['category_id'] ?? null, function ($query, $categoryId) {
            $query->where('category_id', $categoryId);
        });

        // 上架日期範圍篩選
        $query->when($filters['published_start_date'] ?? null, function ($query, $startDate) {
            $query->where('publish_date', '>=', $startDate);
        });

        $query->when($filters['published_end_date'] ?? null, function ($query, $endDate) {
            $query->where('publish_date', '<=', $endDate);
        });

        // 狀態篩選
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

    // 前台查詢 scope
    public function scopeFrontend($query)
    {
        return $query->active()
                    ->where('publish_date', '<=', now());
    }

    /**
     * 篩選來自特定 RSS 來源的文章
     * ArticleRepository::getStaleRssArticles() 有使用到
     */
    public function scopeFromRss($query, string $provider = null)
    {
        $query = $query->whereNotNull('source_provider')
                       ->whereNotNull('source_guid_hash');
        
        if ($provider) {
            $query->where('source_provider', $provider);
        }

        return $query;
    }

    /**
     * 根據 RSS GUID Hash 篩選文章
     * ArticleRepository::findByGuidHash() 有使用到
     */
    public function scopeByRssGuid($query, string $provider, string $guidHash)
    {
        return $query->where('source_provider', $provider)
                    ->where('source_guid_hash', $guidHash);
    }

}
