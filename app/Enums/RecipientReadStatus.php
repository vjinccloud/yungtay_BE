<?php

namespace App\Enums;

enum RecipientReadStatus: string
{
    case UNREAD = 'unread';
    case READ = 'read';

    /**
     * 取得狀態顯示文字
     */
    public function label(): string
    {
        return match($this) {
            self::UNREAD => '未讀',
            self::READ => '已讀',
        };
    }

    /**
     * 取得狀態顏色 CSS class
     */
    public function colorClass(): string
    {
        return match($this) {
            self::UNREAD => 'text-warning',
            self::READ => 'text-muted',
        };
    }

    /**
     * 取得狀態圖示
     */
    public function icon(): string
    {
        return match($this) {
            self::UNREAD => 'fa fa-envelope',
            self::READ => 'fa fa-envelope-open',
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
     * 檢查是否為未讀狀態
     */
    public function isUnread(): bool
    {
        return $this === self::UNREAD;
    }

    /**
     * 檢查是否為已讀狀態
     */
    public function isRead(): bool
    {
        return $this === self::READ;
    }
}