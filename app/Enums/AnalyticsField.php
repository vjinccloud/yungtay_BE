<?php

namespace App\Enums;

/**
 * 統計欄位 Enum
 *
 * 用於 Analytics 系統統一管理所有統計欄位
 * 避免在 Repository 中重複定義欄位名稱
 */
enum AnalyticsField: string
{
    // 基礎觀看數統計
    case MALE_VIEWS = 'male_views';
    case FEMALE_VIEWS = 'female_views';
    case MEMBER_VIEWS = 'member_views';
    case GUEST_VIEWS = 'guest_views';
    case TOTAL_VIEWS = 'total_views';

    // 年齡層統計（7 個區間）
    case AGE_0_10 = 'age_0_10';
    case AGE_11_20 = 'age_11_20';
    case AGE_21_30 = 'age_21_30';
    case AGE_31_40 = 'age_31_40';
    case AGE_41_50 = 'age_41_50';
    case AGE_51_60 = 'age_51_60';
    case AGE_61_PLUS = 'age_61_plus';

    // 男性年齡層統計（7 個區間）
    case MALE_AGE_0_10 = 'male_age_0_10';
    case MALE_AGE_11_20 = 'male_age_11_20';
    case MALE_AGE_21_30 = 'male_age_21_30';
    case MALE_AGE_31_40 = 'male_age_31_40';
    case MALE_AGE_41_50 = 'male_age_41_50';
    case MALE_AGE_51_60 = 'male_age_51_60';
    case MALE_AGE_61_PLUS = 'male_age_61_plus';

    // 女性年齡層統計（7 個區間）
    case FEMALE_AGE_0_10 = 'female_age_0_10';
    case FEMALE_AGE_11_20 = 'female_age_11_20';
    case FEMALE_AGE_21_30 = 'female_age_21_30';
    case FEMALE_AGE_31_40 = 'female_age_31_40';
    case FEMALE_AGE_41_50 = 'female_age_41_50';
    case FEMALE_AGE_51_60 = 'female_age_51_60';
    case FEMALE_AGE_61_PLUS = 'female_age_61_plus';

    /**
     * 取得所有基礎觀看數欄位
     */
    public static function basicViews(): array
    {
        return [
            self::MALE_VIEWS->value,
            self::FEMALE_VIEWS->value,
            self::MEMBER_VIEWS->value,
            self::GUEST_VIEWS->value,
            self::TOTAL_VIEWS->value,
        ];
    }

    /**
     * 取得所有年齡層欄位
     */
    public static function ageGroups(): array
    {
        return [
            self::AGE_0_10->value,
            self::AGE_11_20->value,
            self::AGE_21_30->value,
            self::AGE_31_40->value,
            self::AGE_41_50->value,
            self::AGE_51_60->value,
            self::AGE_61_PLUS->value,
        ];
    }

    /**
     * 取得所有男性年齡層欄位
     */
    public static function maleAgeGroups(): array
    {
        return [
            self::MALE_AGE_0_10->value,
            self::MALE_AGE_11_20->value,
            self::MALE_AGE_21_30->value,
            self::MALE_AGE_31_40->value,
            self::MALE_AGE_41_50->value,
            self::MALE_AGE_51_60->value,
            self::MALE_AGE_61_PLUS->value,
        ];
    }

    /**
     * 取得所有女性年齡層欄位
     */
    public static function femaleAgeGroups(): array
    {
        return [
            self::FEMALE_AGE_0_10->value,
            self::FEMALE_AGE_11_20->value,
            self::FEMALE_AGE_21_30->value,
            self::FEMALE_AGE_31_40->value,
            self::FEMALE_AGE_41_50->value,
            self::FEMALE_AGE_51_60->value,
            self::FEMALE_AGE_61_PLUS->value,
        ];
    }

    /**
     * 取得所有統計欄位（用於 SUM 和 COALESCE）
     */
    public static function all(): array
    {
        return array_merge(
            self::basicViews(),
            self::ageGroups(),
            self::maleAgeGroups(),
            self::femaleAgeGroups()
        );
    }

    /**
     * 檢查是否為數值統計欄位（用於排序判斷）
     */
    public static function isNumericField(string $field): bool
    {
        return in_array($field, self::all(), true);
    }
}
