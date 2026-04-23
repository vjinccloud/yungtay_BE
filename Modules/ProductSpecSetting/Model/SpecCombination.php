<?php

namespace Modules\ProductSpecSetting\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SpecCombination extends Model
{
    use BaseModelTrait, HasTranslations;

    protected $table = 'spec_combinations';

    public $translatable = ['name'];

    protected $fillable = [
        'name', 'seq', 'status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'seq' => 'integer',
    ];

    // ===== Scopes =====

    public function scopeOrdered($query)
    {
        return $query->orderBy('seq', 'asc')->orderBy('id', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // ===== Relations =====

    /**
     * 此組合包含的群組（透過 pivot）
     */
    public function combinationGroups()
    {
        return $this->hasMany(SpecCombinationGroup::class, 'spec_combination_id');
    }

    /**
     * 直接取得關聯的 SpecGroup
     */
    public function specGroups()
    {
        return $this->belongsToMany(SpecGroup::class, 'spec_combination_groups', 'spec_combination_id', 'spec_group_id')
            ->withTimestamps();
    }

    /**
     * 組合標籤，例如「顏色 + 尺寸」
     */
    public function getCombinationLabelAttribute(): string
    {
        $groups = $this->specGroups()->ordered()->get();
        return $groups->map(fn($g) => $g->getTranslation('name', 'zh_TW'))->implode(' + ');
    }
}
