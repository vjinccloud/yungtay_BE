<?php

namespace Modules\ProductSpecSetting\Model;

use Illuminate\Database\Eloquent\Model;

class SpecCombinationGroup extends Model
{
    protected $table = 'spec_combination_groups';

    protected $fillable = [
        'spec_combination_id', 'spec_group_id',
    ];

    protected $casts = [
        'spec_combination_id' => 'integer',
        'spec_group_id' => 'integer',
    ];

    // ===== Relations =====

    public function combination()
    {
        return $this->belongsTo(SpecCombination::class, 'spec_combination_id');
    }

    public function group()
    {
        return $this->belongsTo(SpecGroup::class, 'spec_group_id');
    }
}
