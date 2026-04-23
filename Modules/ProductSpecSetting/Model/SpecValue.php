<?php

namespace Modules\ProductSpecSetting\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SpecValue extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'spec_values';

    protected $fillable = [
        'spec_group_id', 'name', 'seq', 'status',
        'created_by', 'updated_by',
    ];

    public $translatable = ['name'];

    protected $casts = [
        'status' => 'boolean',
        'spec_group_id' => 'integer',
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

    public function group()
    {
        return $this->belongsTo(SpecGroup::class, 'spec_group_id');
    }
}
