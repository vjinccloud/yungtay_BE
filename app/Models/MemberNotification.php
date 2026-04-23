<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use App\Enums\NotificationStatus;
use App\Enums\TargetType;
use App\Enums\SendMode;
use Illuminate\Database\Eloquent\Casts\Attribute;
class MemberNotification extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $fillable = [
        'title',
        'message',
        'target_type',
        'send_mode',
        'scheduled_at',
        'status',
        'sent_at',
    ];

    protected $translatable = [
        'title',
        'message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'target_type' => TargetType::class,
        'send_mode' => SendMode::class,
        'status' => NotificationStatus::class,
    ];

    public $event_title = '會員通知';

    protected $with = ['creator', 'recipients'];

    public function recipients(): HasMany
    {
        return $this->hasMany(MemberNotificationRecipient::class);
    }


    public function scopeFilter(Builder $query, array $filters): void
    {
        $query->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
              ->when($filters['target_type'] ?? null, fn($q, $type) => $q->where('target_type', $type))
              ->when($filters['keyword'] ?? null, function ($q, $keyword) {
                  $q->where(fn($sub) => $sub->where('title', 'like', "%{$keyword}%")
                                           ->orWhere('message', 'like', "%{$keyword}%"));
              });
    }

    /**
     * 檢查是否已發送
     */
    public function isSent(): bool
    {
        return $this->status === NotificationStatus::SENT;
    }

    /**
     * 檢查是否發送失敗
     */
    public function isFailed(): bool
    {
        return $this->status === NotificationStatus::FAILED;
    }

    /**
     * 檢查是否可以取消
     */
    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }

    /**
     * 檢查是否為立即發送模式
     */
    public function isImmediateMode(): bool
    {
        return $this->send_mode === SendMode::IMMEDIATE;
    }

    /**
     * 檢查是否為排程發送模式
     */
    public function isScheduledMode(): bool
    {
        return $this->send_mode === SendMode::SCHEDULED;
    }

    /**
     * 檢查是否針對全體會員
     */
    public function isForAllMembers(): bool
    {
        return $this->target_type === TargetType::ALL;
    }

    /**
     * 檢查是否針對特定會員
     */
    public function isForSpecificMembers(): bool
    {
        return $this->target_type === TargetType::SPECIFIC;
    }
}
