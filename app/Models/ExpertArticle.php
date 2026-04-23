<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ExpertArticle extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'expert_articles';

    protected $fillable = [
        'expert_id',
        'title',
        'content',
        'description',
        'tags',
        'sdgs',
        'published_date',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    public $translatable = ['title', 'content'];

    protected $casts = [
        'is_active' => 'boolean',
        'published_date' => 'date',
        'sdgs' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['creator', 'updater', 'image', 'expert'];

    /**
     * 建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    /**
     * 修改者
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }

    /**
     * 所屬專家
     */
    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class, 'expert_id');
    }

    /**
     * 文章圖片
     */
    public function image(): MorphOne
    {
        return $this->morphOne(ImageManagement::class, 'attachable');
    }

    /**
     * 操作紀錄標題
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?: '專家文章：' . $this->getTranslation('title', 'zh_TW'),
            set: fn(string $value) => $value,
        );
    }

    /**
     * 篩選
     */
    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("JSON_EXTRACT(title, '$.zh_TW') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_EXTRACT(content, '$.zh_TW') LIKE ?", ["%{$search}%"]);
                });
            })
            ->when($filters['expert_id'] ?? null, function ($query, $expertId) {
                $query->where('expert_id', $expertId);
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            });
    }
}
