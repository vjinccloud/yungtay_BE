<?php

namespace App\Traits;

/**
 * Analytics 參數驗證 Trait
 *
 * 用途：統一驗證 Analytics 相關的排序參數，防止 SQL 注入攻擊
 *
 * 使用方式：
 * 1. 在 Controller 中使用此 Trait
 * 2. 呼叫 validateSortParams() 方法驗證排序參數
 *
 * 安全原則：
 * - sortColumn 必須在白名單中
 * - sortDirection 只允許 asc 或 desc
 * - 使用映射表確保欄位名稱安全
 */
trait ValidatesAnalyticsParams
{
    /**
     * 允許的排序欄位白名單（所有統計報表通用）
     *
     * @var array
     */
    protected $allowedSortColumns = [
        'category_name',
        'total_views',
        'male_views',
        'female_views',
        'member_views',
        'guest_views',
        'age_0_10',
        'age_11_20',
        'age_21_30',
        'age_31_40',
        'age_41_50',
        'age_51_60',
        'age_61_plus',
        'age_unknown',
        'male_age_0_10',
        'male_age_11_20',
        'male_age_21_30',
        'male_age_31_40',
        'male_age_41_50',
        'male_age_51_60',
        'male_age_61_plus',
        'female_age_0_10',
        'female_age_11_20',
        'female_age_21_30',
        'female_age_31_40',
        'female_age_41_50',
        'female_age_51_60',
        'female_age_61_plus',
    ];

    /**
     * 允許的排序方向白名單
     *
     * @var array
     */
    protected $allowedSortDirections = ['asc', 'desc'];

    /**
     * 驗證並清理排序參數
     *
     * @param string|null $sortColumn 排序欄位
     * @param string|null $sortDirection 排序方向
     * @return array ['sortColumn' => string, 'sortDirection' => string]
     */
    protected function validateSortParams(?string $sortColumn, ?string $sortDirection): array
    {
        // 預設值
        $validatedColumn = 'total_views';
        $validatedDirection = 'desc';

        // 驗證 sortColumn（白名單）
        // ✅ 修正：檢查是否為非空字串且在白名單中
        if (!empty($sortColumn) && in_array($sortColumn, $this->allowedSortColumns, true)) {
            $validatedColumn = $sortColumn;
        }

        // 驗證 sortDirection（只允許 asc/desc）
        // ✅ 修正：檢查是否為非空字串且在白名單中
        if (!empty($sortDirection) && in_array(strtolower($sortDirection), $this->allowedSortDirections, true)) {
            $validatedDirection = strtolower($sortDirection);
        }

        return [
            'sortColumn' => $validatedColumn,
            'sortDirection' => $validatedDirection,
        ];
    }

    /**
     * 驗證分頁參數
     *
     * @param int|null $length 每頁筆數
     * @return int
     */
    protected function validatePerPage(?int $length): int
    {
        // 預設 25 筆，最小 10 筆，最大 100 筆
        $perPage = $length ?? 25;

        if ($perPage < 10) {
            $perPage = 10;
        }

        if ($perPage > 100) {
            $perPage = 100;
        }

        return $perPage;
    }

    /**
     * 驗證日期格式
     *
     * @param string|null $date 日期字串
     * @return string|null
     */
    protected function validateDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        // 驗證日期格式 (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        // 驗證日期有效性
        $parts = explode('-', $date);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            return null;
        }

        return $date;
    }
}
