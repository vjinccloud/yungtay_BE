<?php

namespace App\Enums;

enum RecipientSentStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';

    /**
     * 取得狀態顯示文字
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => '待發送',
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
            self::PENDING => 'text-warning',
            self::SENT => 'text-success',
            self::FAILED => 'text-danger',
        };
    }

    /**
     * 取得狀態圖示
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'fa fa-clock',
            self::SENT => 'fa fa-check',
            self::FAILED => 'fa fa-times',
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
     * 檢查是否為成功狀態
     */
    public function isSuccessful(): bool
    {
        return $this === self::SENT;
    }

    /**
     * 檢查是否為失敗狀態
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}