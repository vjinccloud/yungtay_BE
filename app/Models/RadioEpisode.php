<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class RadioEpisode extends Model
{
    use HasTranslations;
    use BaseModelTrait;

    protected $fillable = [
        'radio_id',
        'season',
        'episode_number',
        'audio_path',
        'duration',
        'duration_text',
        'description',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by'
    ];

    public $translatable = [
        'duration_text',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'season' => 'integer',
        'episode_number' => 'integer',
        'duration' => 'integer'
    ];

    protected $with = ['created_user', 'updated_user'];

    // ==================== 關聯 ====================

    /**
     * 所屬廣播
     */
    public function radio()
    {
        return $this->belongsTo(Radio::class);
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

    // ==================== 屬性存取器 ====================

    /**
     * 操作紀錄標題
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ?: '廣播集數：第' . $this->season . '季第' . $this->episode_number . '集',
            set: fn (string $value) => $value,
        );
    }

    // ==================== Scopes ====================

    /**
     * 僅啟用的集數
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 依排序順序排列
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * 依指定季數篩選
     */
    public function scopeBySeason($query, $season)
    {
        return $query->where('season', $season);
    }

    /**
     * 依集數編號排列
     */
    public function scopeOrderedByEpisode($query)
    {
        return $query->orderBy('episode_number', 'asc');
    }
}
