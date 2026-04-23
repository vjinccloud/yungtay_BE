<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ExpertField extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'expert_fields';

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    public $translatable = ['name'];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 取得此領域的所有專家
     */
    public function experts(): BelongsToMany
    {
        return $this->belongsToMany(Expert::class, 'expert_field_relations', 'field_id', 'expert_id')
                    ->withTimestamps();
    }

    /**
     * 取得此領域的專家數量
     */
    public function getExpertsCountAttribute(): int
    {
        return $this->experts()->count();
    }

    /**
     * 操作紀錄標題
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?: '專家領域：' . $this->getTranslation('name', 'zh_TW'),
            set: fn(string $value) => $value,
        );
    }

    /**
     * 篩選
     */
    public function scopeFilter($query, array $filters)
    {
        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_EXTRACT(name, '$.zh_TW') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"]);
            });
        });
    }
}
