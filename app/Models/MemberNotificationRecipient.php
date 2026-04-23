<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\RecipientSentStatus;
use App\Enums\RecipientReadStatus;

class MemberNotificationRecipient extends Model
{
    protected $fillable = [
        'member_notification_id',
        'user_id',
        'sent_status',
        'sent_at',
        'read_status',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'sent_status' => RecipientSentStatus::class,
        'read_status' => RecipientReadStatus::class,
    ];

    protected $with = ['user'];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(MemberNotification::class, 'member_notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeUnread(Builder $query): void
    {
        $query->where('read_status', RecipientReadStatus::UNREAD);
    }

    public function scopeRead(Builder $query): void
    {
        $query->where('read_status', RecipientReadStatus::READ);
    }

    public function scopeSent(Builder $query): void
    {
        $query->where('sent_status', RecipientSentStatus::SENT);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('sent_status', RecipientSentStatus::PENDING);
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('sent_status', RecipientSentStatus::FAILED);
    }

    /**
     * 檢查是否已發送
     */
    public function isSent(): bool
    {
        return $this->sent_status === RecipientSentStatus::SENT;
    }

    /**
     * 檢查是否發送失敗
     */
    public function isFailed(): bool
    {
        return $this->sent_status === RecipientSentStatus::FAILED;
    }

    /**
     * 檢查是否已讀
     */
    public function isRead(): bool
    {
        return $this->read_status === RecipientReadStatus::READ;
    }

    /**
     * 檢查是否未讀
     */
    public function isUnread(): bool
    {
        return $this->read_status === RecipientReadStatus::UNREAD;
    }
}
