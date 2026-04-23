<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'provider_name',
        'provider_avatar',
        'provider_data',
    ];

    protected $casts = [
        'provider_data' => 'array',
    ];

    /**
     * 關聯到用戶
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}