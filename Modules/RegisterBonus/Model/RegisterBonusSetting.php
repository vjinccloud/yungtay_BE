<?php

namespace Modules\RegisterBonus\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;

class RegisterBonusSetting extends Model
{
    use BaseModelTrait;

    protected $table = 'register_bonus_settings';

    protected $fillable = [
        'is_active',
        'bonus_amount',
        'expiry_type',
        'expiry_days',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'bonus_amount' => 'integer',
        'expiry_days' => 'integer',
    ];
}
