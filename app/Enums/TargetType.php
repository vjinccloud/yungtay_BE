<?php

namespace App\Enums;

enum TargetType: string
{
    case ALL = 'all';
    case SPECIFIC = 'specific';

    /**
     * 取得類型顯示文字
     */
    public function label(): string
    {
        return match($this) {
            self::ALL => '全體會員',
            self::SPECIFIC => '指定會員',
        };
    }

    /**
     * 取得類型圖示
     */
    public function icon(): string
    {
        return match($this) {
            self::ALL => 'fa fa-users',
            self::SPECIFIC => 'fa fa-user-friends',
        };
    }

    /**
     * 取得所有類型選項（用於表單）
     */
    public static function options(): array
    {
        return array_map(
            fn(self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    /**
     * 檢查是否需要選擇用戶
     */
    public function requiresUserSelection(): bool
    {
        return $this === self::SPECIFIC;
    }
}