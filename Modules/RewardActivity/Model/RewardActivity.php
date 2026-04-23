<?php

namespace Modules\RewardActivity\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardActivity extends Model
{
    use BaseModelTrait, SoftDeletes;

    protected $table = 'reward_activities';

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'description',
        'status',
        'show_on_frontend',
        'promo_code',
        'condition_type',
        'condition_order_total',
        'condition_category_ids',
        'reward_type',
        'reward_value',
        'credit_expiry_type',
        'credit_expiry_days',
        'redemption_limit_type',
        'redemption_site_total',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'show_on_frontend' => 'boolean',
        'condition_order_total' => 'integer',
        'condition_category_ids' => 'array',
        'reward_value' => 'integer',
        'credit_expiry_days' => 'integer',
        'redemption_site_total' => 'integer',
    ];
}
