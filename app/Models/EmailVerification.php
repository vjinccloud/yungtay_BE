<?php

namespace App\Models;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerification extends Model
{
    use BaseModelTrait;

    protected $table = 'email_verifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * 關聯：驗證記錄所屬的用戶
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 檢查 token 是否過期
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * 檢查 token 是否仍然有效
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Scope：查詢有效的（未過期的）驗證記錄
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope：查詢過期的驗證記錄
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope：根據 token 查詢
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $token
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('token', $token);
    }

    /**
     * Scope：根據用戶 ID 查詢
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUserId($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}