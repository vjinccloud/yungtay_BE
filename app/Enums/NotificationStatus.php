<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case SCHEDULED = 'scheduled';
    case SENDING = 'sending';
    case SENT = 'sent';
    case FAILED = 'failed';

    /**
     * 取得狀態顯示文字
     */
    public function label(): string
    {
        return match($this) {
            self::SCHEDULED => '已排程',
            self::SENDING => '發送中',
            self::SENT => '已發送',
            self::FAILED => '發送失敗',
        };
    }

    /**
     * 取得狀態顏色 CSS class
     */
    public function colorClass(): string
    {
        return match($this) {
            self::SCHEDULED => 'text-warning',
            self::SENDING => 'text-info',
            self::SENT => 'text-success',
            self::FAILED => 'text-danger',
        };
    }

    /**
     * 取得所有狀態選項（用於表單）
     */
    public static function options(): array
    {
        return array_map(
            fn(self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    /**
     * 檢查是否為最終狀態（不可再變更）
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::SENT, self::FAILED]);
    }

    /**
     * 檢查是否可以取消
     */
    public function isCancellable(): bool
    {
        return $this === self::SCHEDULED;
    }
}