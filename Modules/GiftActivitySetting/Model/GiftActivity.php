<?php

namespace Modules\GiftActivitySetting\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftActivity extends Model
{
    use BaseModelTrait, SoftDeletes;

    protected $table = 'gift_activities';

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'status',
        'condition_type',
        'condition_amount',
        'condition_category_ids',
        'gift_products',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date'             => 'date',
        'end_date'               => 'date',
        'condition_amount'       => 'integer',
        'condition_category_ids' => 'array',
        'gift_products'          => 'array',
    ];
}
