<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
class DramaTheme extends Model
{
    use HasFactory, HasTranslations, BaseModelTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'drama_themes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    /**
     * The translatable attributes.
     *
     * @var array
     */
    public $translatable = ['name'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 取得屬於此主題的影音
     */
    public function dramas(): BelongsToMany
    {
        return $this->belongsToMany(Drama::class, 'drama_theme_relations', 'theme_id', 'drama_id')
                    ->withPivot('sort_order')
                    ->withTimestamps()
                    ->orderBy('drama_theme_relations.sort_order');
    }

    /**
     * 取得此主題下的影音數量
     */
    public function getDramasCountAttribute(): int
    {
        return $this->dramas()->count();
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
            ?: '影音主題 ：'.$this->getTranslation('name', 'zh_TW'),
            set: fn (string $value) => $value,
        );
    }

    /**
     * Scope a query to filter themes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $filters)
    {
        return $query
        // 搜尋名稱（中英文）
        ->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_EXTRACT(name, '$.zh_TW') LIKE ?", ["%{$search}%"])
                ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"]);
            });
        });
    }

    /**
     * 取得此主題的關聯關係
     */
    public function dramaRelations()
    {
        return $this->hasMany(DramaThemeRelation::class, 'theme_id');
    }

}
