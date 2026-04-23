<?php

namespace Modules\ProductSpecSetting\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SpecGroup extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'spec_groups';

    protected $fillable = [
        'name', 'seq', 'status',
        'created_by', 'updated_by',
    ];

    public $translatable = ['name'];

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

    public function values()
    {
        return $this->hasMany(SpecValue::class, 'spec_group_id')->ordered();
    }

    public function activeValues()
    {
        return $this->hasMany(SpecValue::class, 'spec_group_id')->active()->ordered();
    }
}
