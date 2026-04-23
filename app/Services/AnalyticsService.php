<?php

namespace App\Services;

use App\Repositories\AnalyticsRepository;
use Illuminate\Support\Facades\Cache;

/**
 * 數據分析 Service
 *
 * 負責處理數據分析的業務邏輯、百分比計算、資料格式化
 */
class AnalyticsService
{
    protected $repository;

    /**
     * 快取時間（1 小時）
     */
    protected $cacheTime = 3600;

    public function __construct(AnalyticsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 獲取新聞分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateArticleCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        return $this->repository->paginateArticleCategories($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 獲取廣播分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateRadioCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        return $this->repository->paginateRadioCategories($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 獲取影音主分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateDramaMainCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        return $this->repository->paginateDramaMainCategories($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 獲取影音子分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateDramaSubCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        return $this->repository->paginateDramaSubCategories($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 獲取節目主分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateProgramMainCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        return $this->repository->paginateProgramMainCategories($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 獲取節目子分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateProgramSubCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        return $this->repository->paginateProgramSubCategories($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 獲取影音主分類統計
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return array
     */
    public function getDramaMainCategoryStats(string $startDate, string $endDate): array
    {
        $cacheKey = "analytics:dramas:main:{$startDate}:{$endDate}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($startDate, $endDate) {
            $categories = $this->repository->getDramaMainCategoryStats($startDate, $endDate);

            return $this->formatCategoryStatistics($categories);
        });
    }

    /**
     * 獲取影音子分類統計
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return array
     */
    public function getDramaSubCategoryStats(string $startDate, string $endDate): array
    {
        $cacheKey = "analytics:dramas:sub:{$startDate}:{$endDate}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($startDate, $endDate) {
            $categories = $this->repository->getDramaSubCategoryStats($startDate, $endDate);

            return $this->formatCategoryStatistics($categories, true);
        });
    }

    /**
     * 獲取節目主分類統計
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return array
     */
    public function getProgramMainCategoryStats(string $startDate, string $endDate): array
    {
        $cacheKey = "analytics:programs:main:{$startDate}:{$endDate}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($startDate, $endDate) {
            $categories = $this->repository->getProgramMainCategoryStats($startDate, $endDate);

            return $this->formatCategoryStatistics($categories);
        });
    }

    /**
     * 獲取節目子分類統計
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return array
     */
    public function getProgramSubCategoryStats(string $startDate, string $endDate): array
    {
        $cacheKey = "analytics:programs:sub:{$startDate}:{$endDate}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($startDate, $endDate) {
            $categories = $this->repository->getProgramSubCategoryStats($startDate, $endDate);

            return $this->formatCategoryStatistics($categories, true);
        });
    }

    /**
     * 格式化統計資料（單一內容類型，如新聞、廣播）
     *
     * @param array $rawStats 原始統計數據
     * @return array
     */
    protected function formatStatistics(array $rawStats): array
    {
        $totalViews = $rawStats['total_views'] ?? 0;

        return [
            'summary' => [
                'total_views' => (int) $totalViews,
                'unique_views' => (int) ($rawStats['unique_views'] ?? 0),
                'member_views' => (int) ($rawStats['member_views'] ?? 0),
                'guest_views' => (int) ($rawStats['guest_views'] ?? 0),
            ],
            'gender' => $this->formatGenderStats($rawStats, $totalViews),
            'age' => $this->formatAgeStats($rawStats, $totalViews),
            'male_age' => $this->formatMaleAgeStats($rawStats, $totalViews),
            'female_age' => $this->formatFemaleAgeStats($rawStats, $totalViews),
        ];
    }

    /**
     * 格式化分類統計資料（影音/節目的主分類或子分類）
     *
     * @param \Illuminate\Support\Collection $categories 分類集合
     * @param bool $isSubCategory 是否為子分類
     * @return array
     */
    protected function formatCategoryStatistics($categories, bool $isSubCategory = false): array
    {
        $formatted = [];

        foreach ($categories as $category) {
            $totalViews = $category->total_views ?? 0;

            // 子分類顯示「主分類_子分類」格式
            $categoryName = $isSubCategory && $category->category && $category->category->parent
                ? ($category->category->parent->name['zh_TW'] ?? $category->category->parent->name) . '_' .
                  ($category->category->name['zh_TW'] ?? $category->category->name)
                : ($category->category->name['zh_TW'] ?? $category->category->name ?? '未知分類');

            $formatted[] = [
                'category_id' => $category->category_id,
                'category_name' => $categoryName,
                'summary' => [
                    'total_views' => (int) $totalViews,
                    'unique_views' => (int) ($category->unique_views ?? 0),
                    'member_views' => (int) ($category->member_views ?? 0),
                    'guest_views' => (int) ($category->guest_views ?? 0),
                ],
                'gender' => $this->formatGenderStats($category->toArray(), $totalViews),
                'age' => $this->formatAgeStats($category->toArray(), $totalViews),
                'male_age' => $this->formatMaleAgeStats($category->toArray(), $totalViews),
                'female_age' => $this->formatFemaleAgeStats($category->toArray(), $totalViews),
            ];
        }

        return $formatted;
    }

    /**
     * 格式化性別統計資料
     *
     * @param array $stats 統計數據
     * @param int $totalViews 總觀看數
     * @return array
     */
    protected function formatGenderStats(array $stats, int $totalViews): array
    {
        $maleViews = (int) ($stats['male_views'] ?? 0);
        $femaleViews = (int) ($stats['female_views'] ?? 0);
        $memberViews = (int) ($stats['member_views'] ?? 0);
        $guestViews = (int) ($stats['guest_views'] ?? 0);

        // 計算性別總計（僅包含有性別資料的會員）
        $genderTotal = $maleViews + $femaleViews;

        return [
            'data' => [
                ['label' => '男性', 'value' => $maleViews, 'percentage' => $this->calculatePercentage($maleViews, $genderTotal)],
                ['label' => '女性', 'value' => $femaleViews, 'percentage' => $this->calculatePercentage($femaleViews, $genderTotal)],
            ],
            'total' => ['value' => $genderTotal, 'percentage' => 100.0],
            'member_info' => [
                'member_views' => $memberViews,
                'guest_views' => $guestViews,
                'total_views' => $totalViews,
            ],
        ];
    }

    /**
     * 格式化年齡統計資料
     *
     * @param array $stats 統計數據
     * @param int $totalViews 總觀看數
     * @return array
     */
    protected function formatAgeStats(array $stats, int $totalViews): array
    {
        $age0_10 = (int) ($stats['age_0_10'] ?? 0);
        $age11_20 = (int) ($stats['age_11_20'] ?? 0);
        $age21_30 = (int) ($stats['age_21_30'] ?? 0);
        $age31_40 = (int) ($stats['age_31_40'] ?? 0);
        $age41_50 = (int) ($stats['age_41_50'] ?? 0);
        $age51_60 = (int) ($stats['age_51_60'] ?? 0);
        $age61Plus = (int) ($stats['age_61_plus'] ?? 0);
        $unknownAge = (int) ($stats['unknown_age'] ?? 0);

        return [
            'data' => [
                ['label' => '0-10歲', 'value' => $age0_10, 'percentage' => $this->calculatePercentage($age0_10, $totalViews)],
                ['label' => '11-20歲', 'value' => $age11_20, 'percentage' => $this->calculatePercentage($age11_20, $totalViews)],
                ['label' => '21-30歲', 'value' => $age21_30, 'percentage' => $this->calculatePercentage($age21_30, $totalViews)],
                ['label' => '31-40歲', 'value' => $age31_40, 'percentage' => $this->calculatePercentage($age31_40, $totalViews)],
                ['label' => '41-50歲', 'value' => $age41_50, 'percentage' => $this->calculatePercentage($age41_50, $totalViews)],
                ['label' => '51-60歲', 'value' => $age51_60, 'percentage' => $this->calculatePercentage($age51_60, $totalViews)],
                ['label' => '61+歲', 'value' => $age61Plus, 'percentage' => $this->calculatePercentage($age61Plus, $totalViews)],
                ['label' => '未知', 'value' => $unknownAge, 'percentage' => $this->calculatePercentage($unknownAge, $totalViews)],
            ],
            'total' => ['value' => $totalViews, 'percentage' => 100.0],
        ];
    }

    /**
     * 格式化男性年齡統計資料
     *
     * @param array $stats 統計數據
     * @param int $totalViews 總觀看數
     * @return array
     */
    protected function formatMaleAgeStats(array $stats, int $totalViews): array
    {
        $maleAge0_10 = (int) ($stats['male_age_0_10'] ?? 0);
        $maleAge11_20 = (int) ($stats['male_age_11_20'] ?? 0);
        $maleAge21_30 = (int) ($stats['male_age_21_30'] ?? 0);
        $maleAge31_40 = (int) ($stats['male_age_31_40'] ?? 0);
        $maleAge41_50 = (int) ($stats['male_age_41_50'] ?? 0);
        $maleAge51_60 = (int) ($stats['male_age_51_60'] ?? 0);
        $maleAge61Plus = (int) ($stats['male_age_61_plus'] ?? 0);

        return [
            'data' => [
                ['label' => '男性 0-10歲', 'value' => $maleAge0_10, 'percentage' => $this->calculatePercentage($maleAge0_10, $totalViews)],
                ['label' => '男性 11-20歲', 'value' => $maleAge11_20, 'percentage' => $this->calculatePercentage($maleAge11_20, $totalViews)],
                ['label' => '男性 21-30歲', 'value' => $maleAge21_30, 'percentage' => $this->calculatePercentage($maleAge21_30, $totalViews)],
                ['label' => '男性 31-40歲', 'value' => $maleAge31_40, 'percentage' => $this->calculatePercentage($maleAge31_40, $totalViews)],
                ['label' => '男性 41-50歲', 'value' => $maleAge41_50, 'percentage' => $this->calculatePercentage($maleAge41_50, $totalViews)],
                ['label' => '男性 51-60歲', 'value' => $maleAge51_60, 'percentage' => $this->calculatePercentage($maleAge51_60, $totalViews)],
                ['label' => '男性 61+歲', 'value' => $maleAge61Plus, 'percentage' => $this->calculatePercentage($maleAge61Plus, $totalViews)],
            ],
            'total' => ['value' => $totalViews, 'percentage' => 100.0],
        ];
    }

    /**
     * 格式化女性年齡統計資料
     *
     * @param array $stats 統計數據
     * @param int $totalViews 總觀看數
     * @return array
     */
    protected function formatFemaleAgeStats(array $stats, int $totalViews): array
    {
        $femaleAge0_10 = (int) ($stats['female_age_0_10'] ?? 0);
        $femaleAge11_20 = (int) ($stats['female_age_11_20'] ?? 0);
        $femaleAge21_30 = (int) ($stats['female_age_21_30'] ?? 0);
        $femaleAge31_40 = (int) ($stats['female_age_31_40'] ?? 0);
        $femaleAge41_50 = (int) ($stats['female_age_41_50'] ?? 0);
        $femaleAge51_60 = (int) ($stats['female_age_51_60'] ?? 0);
        $femaleAge61Plus = (int) ($stats['female_age_61_plus'] ?? 0);

        return [
            'data' => [
                ['label' => '女性 0-10歲', 'value' => $femaleAge0_10, 'percentage' => $this->calculatePercentage($femaleAge0_10, $totalViews)],
                ['label' => '女性 11-20歲', 'value' => $femaleAge11_20, 'percentage' => $this->calculatePercentage($femaleAge11_20, $totalViews)],
                ['label' => '女性 21-30歲', 'value' => $femaleAge21_30, 'percentage' => $this->calculatePercentage($femaleAge21_30, $totalViews)],
                ['label' => '女性 31-40歲', 'value' => $femaleAge31_40, 'percentage' => $this->calculatePercentage($femaleAge31_40, $totalViews)],
                ['label' => '女性 41-50歲', 'value' => $femaleAge41_50, 'percentage' => $this->calculatePercentage($femaleAge41_50, $totalViews)],
                ['label' => '女性 51-60歲', 'value' => $femaleAge51_60, 'percentage' => $this->calculatePercentage($femaleAge51_60, $totalViews)],
                ['label' => '女性 61+歲', 'value' => $femaleAge61Plus, 'percentage' => $this->calculatePercentage($femaleAge61Plus, $totalViews)],
            ],
            'total' => ['value' => $totalViews, 'percentage' => 100.0],
        ];
    }

    /**
     * 計算百分比
     *
     * @param int $value 數值
     * @param int $total 總數
     * @return float
     */
    protected function calculatePercentage(int $value, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 1);
    }

    /**
     * 獲取影音主分類清單（用於子分類篩選下拉選單）
     *
     * @return array
     */
    public function getDramaMainCategoriesForFilter(): array
    {
        return $this->repository->getMainCategoriesForFilter('drama');
    }

    /**
     * 獲取節目主分類清單（用於子分類篩選下拉選單）
     *
     * @return array
     */
    public function getProgramMainCategoriesForFilter(): array
    {
        return $this->repository->getMainCategoriesForFilter('program');
    }

}
