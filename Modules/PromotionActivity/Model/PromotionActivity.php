<?php

namespace Modules\PromotionActivity\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionActivity extends Model
{
    use BaseModelTrait, SoftDeletes;

    protected $table = 'promotion_activities';

    protected $fillable = [
        'title',
        'is_active',
        'start_date',
        'end_date',
        'min_amount',
        'discount_amount',
        'category_ids',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'min_amount' => 'integer',
        'discount_amount' => 'integer',
        'category_ids' => 'array',
    ];
}
