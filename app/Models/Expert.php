<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Expert extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'experts';

    protected $fillable = [
        'category_id',
        'name',
        'title',
        'specialty',
        'bio',
        'tags',
        'is_featured',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    public $translatable = ['name', 'title', 'specialty', 'bio'];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['creator', 'updater', 'image', 'category'];

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
     * 所屬分類
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpertCategory::class, 'category_id');
    }

    /**
     * 專家領域（多對多）
     */
    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(ExpertField::class, 'expert_field_relations', 'expert_id', 'field_id')
                    ->withTimestamps();
    }

    /**
     * 專家文章
     */
    public function articles(): HasMany
    {
        return $this->hasMany(ExpertArticle::class, 'expert_id');
    }

    /**
     * 專家頭像圖片
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
            get: fn($value) => $value ?: '專家：' . $this->getTranslation('name', 'zh_TW'),
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
                    $q->whereRaw("JSON_EXTRACT(name, '$.zh_TW') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_EXTRACT(title, '$.zh_TW') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_EXTRACT(specialty, '$.zh_TW') LIKE ?", ["%{$search}%"])
                      ->orWhere('tags', 'like', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            })
            ->when(isset($filters['is_featured']), function ($query) use ($filters) {
                $query->where('is_featured', $filters['is_featured']);
            });
    }
}
