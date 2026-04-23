<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Live extends Model
{
    use HasTranslations;
    use BaseModelTrait;

    protected $fillable = [
        'title',
        'description',
        'youtube_url',
        'remarks',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by'
    ];

    public $translatable = [
        'title',
        'description',
        'remarks'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    protected $with = ['creator', 'updater'];

    public static $event_title = '直播';

    // 關聯：建立者 (admin_users 表)
    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    // 關聯：最後修改者 (admin_users 表)
    public function updater()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }

    /**
     * 搜尋範圍：可以過濾標題關鍵字（不區分大小寫）
     */
    public function scopeFilter($query, array $filters)
    {
        // 搜尋標題（不區分大小寫）
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(title->"$.zh_TW") like ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(title->"$.en") like ?', ["%{$searchLower}%"]);
            });
        });

        return $query;
    }

    /**
     * 操作紀錄標題
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value // 先看有沒有手動覆寫
            ?: '直播：'.$this->getTranslation('title', 'zh_TW'),
            set: fn (string $value) => $value,
        );
    }

    /**
     * YouTube 縮圖關聯
     */
    public function images()
    {
        return $this->morphMany(\App\Models\ImageManagement::class, 'attachable');
    }

    /**
     * 取得 YouTube 縮圖
     */
    public function getThumbnailAttribute()
    {
        $thumbnail = $this->images()
            ->where('image_type', 'video_thumbnail')
            ->first();
        
        if (!$thumbnail || empty($thumbnail->path)) {
            return null;
        }
        // 若為絕對網址，直接回傳；否則透過 Laravel /storage 公開目錄
        if (str_starts_with($thumbnail->path, 'http')) {
            return $thumbnail->path;
        }
        return '/storage/' . ltrim($thumbnail->path, '/');
    }
}
