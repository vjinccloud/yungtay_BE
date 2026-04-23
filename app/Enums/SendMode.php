<?php

namespace App\Enums;

enum SendMode: string
{
    case IMMEDIATE = 'immediate';
    case SCHEDULED = 'scheduled';

    /**
     * 取得模式顯示文字
     */
    public function label(): string
    {
        return match($this) {
            self::IMMEDIATE => '立即發送',
            self::SCHEDULED => '排程發送',
        };
    }

    /**
     * 取得模式圖示
     */
    public function icon(): string
    {
        return match($this) {
            self::IMMEDIATE => 'fa fa-paper-plane',
            self::SCHEDULED => 'fa fa-clock',
        };
    }

    /**
     * 取得所有模式選項（用於表單）
     */
    public static function options(): array
    {
        return array_map(
            fn(self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    /**
     * 檢查是否需要排程時間
     */
    public function requiresScheduledTime(): bool
    {
        return $this === self::SCHEDULED;
    }

    /**
     * 取得對應的通知狀態
     */
    public function getInitialStatus(): NotificationStatus
    {
        return match($this) {
            self::IMMEDIATE => NotificationStatus::SENDING,
            self::SCHEDULED => NotificationStatus::SCHEDULED,
        };
    }
}