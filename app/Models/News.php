<?php

namespace App\Models;

use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class News extends Model
{
    use HasTranslations;
    use BaseModelTrait;

    protected $fillable = [
        'category_id',
        'title',
        'content',
        'description',
        'tags',
        'is_active',
        'is_homepage_featured',
        'is_pinned',
        'published_date',
        'created_by',
        'updated_by'
    ];

    public $translatable = ['title', 'content'];


    protected $casts = [
        'is_active' => 'boolean',
        'is_homepage_featured' => 'boolean',
        'is_pinned' => 'boolean',
        'published_date' => 'date',
        'title' => 'array',
        'content' => 'array',
    ];
    protected $with = ['creator', 'updater', 'image', 'category'];
    // 關聯：建立者 (users 表但使用 admin guard)
    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    // 關聯：最後修改者 (users 表但使用 admin guard)
    public function updater()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }

    // 關聯：分類
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 操作紀錄標題
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value // 先看有沒有手動覆寫
                ?: '最新消息 ：' . $this->getTranslation('title', 'zh_TW'),
            set: fn(string $value) => $value,
        );
    }

    /**
     * 搜尋範圍：可以過濾標題關鍵字、日期區間、啟用狀態…
     */
    public function scopeFilter($query, array $filters)
    {
        // 搜尋邏輯
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title->zh_TW', 'like', "%{$search}%")
                    ->orWhere('title->en',    'like', "%{$search}%")
                    ->orWhere('content->zh_TW', 'like', "%{$search}%")
                    ->orWhere('content->en',  'like', "%{$search}%");
            });
        });

        // 範例：標題關鍵字（單一語系）
        // if (!empty($filters['title'])) {
        //     // 方式一：直接用 JSON path
        //     $locale = app()->getLocale(); // ex: zh_TW or en
        //     $query->where("title->{$locale}", 'like', "%{$filters['title']}%");

        //     // 或者方式二：spatie helper
        //     // $query->whereTranslationLike('title', "%{$filters['title']}%", $locale);
        // }

        // // 範例：上架日期區間
        // if (!empty($filters['start_date'])) {
        //     $query->whereDate('published_at', '>=', $filters['start_date']);
        // }
        // if (!empty($filters['end_date'])) {
        //     $query->whereDate('published_at', '<=', $filters['end_date']);
        // }

        // // 其他條件…（例如 is_active）
        // if (isset($filters['is_active'])) {
        //     $query->where('is_active', $filters['is_active']);
        // }

        return $query;
    }
    // 圖片關聯（單一圖片）
    public function image()
    {
        return $this->morphOne(ImageManagement::class, 'attachable');
    }
}
