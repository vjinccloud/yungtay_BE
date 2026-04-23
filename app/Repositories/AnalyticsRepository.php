<?php

namespace App\Repositories;

use App\Models\CategoryAggregation;
use App\Models\Category;
use App\Enums\AnalyticsField;
use Illuminate\Support\Facades\DB;

/**
 * 數據分析 Repository
 *
 * 負責從 view_demographics 和 category_aggregations 查詢統計數據
 */
class AnalyticsRepository
{

    /**
     * 建立聚合子查詢（統一所有統計欄位的 SUM）
     *
     * @param string $contentType 內容類型（article/radio/drama/program）
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @param string $periodType 期間類型（daily/weekly/monthly/all_time）
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildAggregationSubQuery(
        string $contentType,
        string $startDate,
        string $endDate,
        string $periodType = 'daily'
    ): \Illuminate\Database\Query\Builder {
        // 組裝所有 SUM 欄位（使用 Enum 統一管理）
        $selectRaw = [];
        foreach (AnalyticsField::all() as $field) {
            $selectRaw[] = "SUM({$field}) as {$field}";
        }

        return DB::table('category_aggregations')
            ->select('category_id')
            ->selectRaw(implode(",\n                ", $selectRaw))
            ->where('content_type', $contentType)
            ->where('period_type', $periodType)
            ->whereBetween('period_date', [$startDate, $endDate])
            ->groupBy('category_id');
    }

    /**
     * 套用聚合欄位的 COALESCE 選取（避免 NULL）
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $alias 子查詢別名（預設 'agg'）
     * @return void
     */
    private function applyAggregationSelects(\Illuminate\Database\Eloquent\Builder $query, string $alias = 'agg'): void
    {
        $selectRaw = [];
        foreach (AnalyticsField::all() as $field) {
            $selectRaw[] = "COALESCE({$alias}.{$field}, 0) as {$field}";
        }

        $query->selectRaw(implode(",\n                ", $selectRaw));
    }

    /**
     * 套用排序邏輯（數值欄位 vs 分類名稱）
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param string $aggAlias 聚合別名（預設 'agg'）
     * @return void
     */
    private function applySortLogic(
        \Illuminate\Database\Eloquent\Builder $query,
        string $sortColumn,
        string $sortDirection,
        string $aggAlias = 'agg'
    ): void {
        // 使用 Enum 判斷是否為數值欄位
        if (AnalyticsField::isNumericField($sortColumn)) {
            // 數值欄位排序：使用 CAST 確保數值排序而非字串排序
            $query->orderByRaw("CAST(COALESCE({$aggAlias}.{$sortColumn}, 0) AS UNSIGNED) {$sortDirection}");
        } else {
            // 非數值欄位（category_name）排序
            $query->orderBy($sortColumn, $sortDirection);
        }
    }

    /**
     * 格式化統計資料列（統一 cast 為 int）
     *
     * @param object $row 資料列
     * @return array
     */
    private function formatStatsRow($row): array
    {
        $result = [
            'category_id' => $row->category_id,
            'category_name' => $row->category_name ?? '未知分類',
        ];

        // 所有統計欄位 cast 為 int（使用 Enum）
        foreach (AnalyticsField::all() as $field) {
            $result[$field] = (int) ($row->{$field} ?? 0);
        }

        return $result;
    }

    /**
     * 獲取新聞分類統計（從 category_aggregations）
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return \Illuminate\Support\Collection
     */
    public function getArticleCategoryStats(string $startDate, string $endDate)
    {
        return CategoryAggregation::where('content_type', 'article')
            ->where('period_type', 'daily')
            ->whereBetween('period_date', [$startDate, $endDate])
            ->with('category:id,name')
            ->select('category_id')
            ->selectRaw('
                SUM(male_views) as male_views,
                SUM(female_views) as female_views,
                SUM(member_views) as member_views,
                SUM(guest_views) as guest_views,
                SUM(total_views) as total_views,
                SUM(age_0_10) as age_0_10,
                SUM(age_11_20) as age_11_20,
                SUM(age_21_30) as age_21_30,
                SUM(age_31_40) as age_31_40,
                SUM(age_41_50) as age_41_50,
                SUM(age_51_60) as age_51_60,
                SUM(age_61_plus) as age_61_plus,
                SUM(male_age_0_10) as male_age_0_10,
                SUM(male_age_11_20) as male_age_11_20,
                SUM(male_age_21_30) as male_age_21_30,
                SUM(male_age_31_40) as male_age_31_40,
                SUM(male_age_41_50) as male_age_41_50,
                SUM(male_age_51_60) as male_age_51_60,
                SUM(male_age_61_plus) as male_age_61_plus,
                SUM(female_age_0_10) as female_age_0_10,
                SUM(female_age_11_20) as female_age_11_20,
                SUM(female_age_21_30) as female_age_21_30,
                SUM(female_age_31_40) as female_age_31_40,
                SUM(female_age_41_50) as female_age_41_50,
                SUM(female_age_51_60) as female_age_51_60,
                SUM(female_age_61_plus) as female_age_61_plus
            ')
            ->groupBy('category_id')
            ->get();
    }

    /**
     * 獲取廣播分類統計（從 category_aggregations）
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return \Illuminate\Support\Collection
     */
    public function getRadioCategoryStats(string $startDate, string $endDate)
    {
        return CategoryAggregation::where('content_type', 'radio')
            ->where('period_type', 'daily')
            ->whereBetween('period_date', [$startDate, $endDate])
            ->with('category:id,name')
            ->select('category_id')
            ->selectRaw('
                SUM(male_views) as male_views,
                SUM(female_views) as female_views,
                SUM(member_views) as member_views,
                SUM(guest_views) as guest_views,
                SUM(total_views) as total_views,
                SUM(age_0_10) as age_0_10,
                SUM(age_11_20) as age_11_20,
                SUM(age_21_30) as age_21_30,
                SUM(age_31_40) as age_31_40,
                SUM(age_41_50) as age_41_50,
                SUM(age_51_60) as age_51_60,
                SUM(age_61_plus) as age_61_plus,
                SUM(male_age_0_10) as male_age_0_10,
                SUM(male_age_11_20) as male_age_11_20,
                SUM(male_age_21_30) as male_age_21_30,
                SUM(male_age_31_40) as male_age_31_40,
                SUM(male_age_41_50) as male_age_41_50,
                SUM(male_age_51_60) as male_age_51_60,
                SUM(male_age_61_plus) as male_age_61_plus,
                SUM(female_age_0_10) as female_age_0_10,
                SUM(female_age_11_20) as female_age_11_20,
                SUM(female_age_21_30) as female_age_21_30,
                SUM(female_age_31_40) as female_age_31_40,
                SUM(female_age_41_50) as female_age_41_50,
                SUM(female_age_51_60) as female_age_51_60,
                SUM(female_age_61_plus) as female_age_61_plus
            ')
            ->groupBy('category_id')
            ->get();
    }

    /**
     * 獲取影音主分類統計（從 category_aggregations）
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return \Illuminate\Support\Collection
     */
    public function getDramaMainCategoryStats(string $startDate, string $endDate)
    {
        return CategoryAggregation::where('content_type', 'drama')
            ->where('period_type', 'daily')
            ->whereBetween('period_date', [$startDate, $endDate])
            ->whereHas('category', function ($query) {
                $query->whereNull('parent_id'); // 主分類
            })
            ->with('category:id,name')
            ->select('category_id')
            ->selectRaw('
                SUM(male_views) as male_views,
                SUM(female_views) as female_views,
                SUM(member_views) as member_views,
                SUM(guest_views) as guest_views,
                SUM(total_views) as total_views,
                SUM(age_0_10) as age_0_10,
                SUM(age_11_20) as age_11_20,
                SUM(age_21_30) as age_21_30,
                SUM(age_31_40) as age_31_40,
                SUM(age_41_50) as age_41_50,
                SUM(age_51_60) as age_51_60,
                SUM(age_61_plus) as age_61_plus,
                SUM(male_age_0_10) as male_age_0_10,
                SUM(male_age_11_20) as male_age_11_20,
                SUM(male_age_21_30) as male_age_21_30,
                SUM(male_age_31_40) as male_age_31_40,
                SUM(male_age_41_50) as male_age_41_50,
                SUM(male_age_51_60) as male_age_51_60,
                SUM(male_age_61_plus) as male_age_61_plus,
                SUM(female_age_0_10) as female_age_0_10,
                SUM(female_age_11_20) as female_age_11_20,
                SUM(female_age_21_30) as female_age_21_30,
                SUM(female_age_31_40) as female_age_31_40,
                SUM(female_age_41_50) as female_age_41_50,
                SUM(female_age_51_60) as female_age_51_60,
                SUM(female_age_61_plus) as female_age_61_plus
            ')
            ->groupBy('category_id')
            ->get();
    }

    /**
     * 獲取影音子分類統計（從 category_aggregations）
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return \Illuminate\Support\Collection
     */
    public function getDramaSubCategoryStats(string $startDate, string $endDate)
    {
        return CategoryAggregation::where('content_type', 'drama')
            ->where('period_type', 'daily')
            ->whereBetween('period_date', [$startDate, $endDate])
            ->whereHas('category', function ($query) {
                $query->whereNotNull('parent_id'); // 子分類
            })
            ->with(['category:id,name,parent_id', 'category.parent:id,name'])
            ->select('category_id')
            ->selectRaw('
                SUM(male_views) as male_views,
                SUM(female_views) as female_views,
                SUM(member_views) as member_views,
                SUM(guest_views) as guest_views,
                SUM(total_views) as total_views,
                SUM(age_0_10) as age_0_10,
                SUM(age_11_20) as age_11_20,
                SUM(age_21_30) as age_21_30,
                SUM(age_31_40) as age_31_40,
                SUM(age_41_50) as age_41_50,
                SUM(age_51_60) as age_51_60,
                SUM(age_61_plus) as age_61_plus,
                SUM(male_age_0_10) as male_age_0_10,
                SUM(male_age_11_20) as male_age_11_20,
                SUM(male_age_21_30) as male_age_21_30,
                SUM(male_age_31_40) as male_age_31_40,
                SUM(male_age_41_50) as male_age_41_50,
                SUM(male_age_51_60) as male_age_51_60,
                SUM(male_age_61_plus) as male_age_61_plus,
                SUM(female_age_0_10) as female_age_0_10,
                SUM(female_age_11_20) as female_age_11_20,
                SUM(female_age_21_30) as female_age_21_30,
                SUM(female_age_31_40) as female_age_31_40,
                SUM(female_age_41_50) as female_age_41_50,
                SUM(female_age_51_60) as female_age_51_60,
                SUM(female_age_61_plus) as female_age_61_plus
            ')
            ->groupBy('category_id')
            ->get();
    }

    /**
     * 獲取節目主分類統計（從 category_aggregations）
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return \Illuminate\Support\Collection
     */
    public function getProgramMainCategoryStats(string $startDate, string $endDate)
    {
        return CategoryAggregation::where('content_type', 'program')
            ->where('period_type', 'daily')
            ->whereBetween('period_date', [$startDate, $endDate])
            ->whereHas('category', function ($query) {
                $query->whereNull('parent_id'); // 主分類
            })
            ->with('category:id,name')
            ->select('category_id')
            ->selectRaw('
                SUM(male_views) as male_views,
                SUM(female_views) as female_views,
                SUM(member_views) as member_views,
                SUM(guest_views) as guest_views,
                SUM(total_views) as total_views,
                SUM(age_0_10) as age_0_10,
                SUM(age_11_20) as age_11_20,
                SUM(age_21_30) as age_21_30,
                SUM(age_31_40) as age_31_40,
                SUM(age_41_50) as age_41_50,
                SUM(age_51_60) as age_51_60,
                SUM(age_61_plus) as age_61_plus,
                SUM(male_age_0_10) as male_age_0_10,
                SUM(male_age_11_20) as male_age_11_20,
                SUM(male_age_21_30) as male_age_21_30,
                SUM(male_age_31_40) as male_age_31_40,
                SUM(male_age_41_50) as male_age_41_50,
                SUM(male_age_51_60) as male_age_51_60,
                SUM(male_age_61_plus) as male_age_61_plus,
                SUM(female_age_0_10) as female_age_0_10,
                SUM(female_age_11_20) as female_age_11_20,
                SUM(female_age_21_30) as female_age_21_30,
                SUM(female_age_31_40) as female_age_31_40,
                SUM(female_age_41_50) as female_age_41_50,
                SUM(female_age_51_60) as female_age_51_60,
                SUM(female_age_61_plus) as female_age_61_plus
            ')
            ->groupBy('category_id')
            ->get();
    }

    /**
     * 獲取節目子分類統計（從 category_aggregations）
     *
     * @param string $startDate 開始日期
     * @param string $endDate 結束日期
     * @return \Illuminate\Support\Collection
     */
    public function getProgramSubCategoryStats(string $startDate, string $endDate)
    {
        return CategoryAggregation::where('content_type', 'program')
            ->where('period_type', 'daily')
            ->whereBetween('period_date', [$startDate, $endDate])
            ->whereHas('category', function ($query) {
                $query->whereNotNull('parent_id'); // 子分類
            })
            ->with(['category:id,name,parent_id', 'category.parent:id,name'])
            ->select('category_id')
            ->selectRaw('
                SUM(male_views) as male_views,
                SUM(female_views) as female_views,
                SUM(member_views) as member_views,
                SUM(guest_views) as guest_views,
                SUM(total_views) as total_views,
                SUM(age_0_10) as age_0_10,
                SUM(age_11_20) as age_11_20,
                SUM(age_21_30) as age_21_30,
                SUM(age_31_40) as age_31_40,
                SUM(age_41_50) as age_41_50,
                SUM(age_51_60) as age_51_60,
                SUM(age_61_plus) as age_61_plus,
                SUM(male_age_0_10) as male_age_0_10,
                SUM(male_age_11_20) as male_age_11_20,
                SUM(male_age_21_30) as male_age_21_30,
                SUM(male_age_31_40) as male_age_31_40,
                SUM(male_age_41_50) as male_age_41_50,
                SUM(male_age_51_60) as male_age_51_60,
                SUM(male_age_61_plus) as male_age_61_plus,
                SUM(female_age_0_10) as female_age_0_10,
                SUM(female_age_11_20) as female_age_11_20,
                SUM(female_age_21_30) as female_age_21_30,
                SUM(female_age_31_40) as female_age_31_40,
                SUM(female_age_41_50) as female_age_41_50,
                SUM(female_age_51_60) as female_age_51_60,
                SUM(female_age_61_plus) as female_age_61_plus
            ')
            ->groupBy('category_id')
            ->get();
    }

    /**
     * 獲取新聞分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件（包含 start_date、end_date）
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateArticleCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        // ✅ 使用統一的子查詢建立方法
        $subQuery = $this->buildAggregationSubQuery('article', $startDate, $endDate);

        // 主查詢：LEFT JOIN 子查詢結果
        $query = Category::where('type', 'article')
            ->where('status', 1)
            ->leftJoinSub($subQuery, 'agg', fn($join) => $join->on('categories.id', '=', 'agg.category_id'))
            ->select(
                'categories.id as category_id',
                'categories.seq as seq',
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(categories.name, "$.zh_TW")) as category_name')
            );

        // ✅ 使用統一的欄位選取方法
        $this->applyAggregationSelects($query, 'agg');

        // ✅ 使用統一的排序方法
        $this->applySortLogic($query, $sortColumn, $sortDirection, 'agg');

        // ✅ 使用統一的格式化方法
        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn($row) => $this->formatStatsRow($row));
    }

    /**
     * 獲取廣播分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件（包含 start_date、end_date）
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateRadioCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        // ✅ 使用統一的子查詢建立方法
        $subQuery = $this->buildAggregationSubQuery('radio', $startDate, $endDate);

        // 主查詢：LEFT JOIN 子查詢結果
        $query = Category::where('type', 'radio')
            ->where('status', 1)
            ->leftJoinSub($subQuery, 'agg', fn($join) => $join->on('categories.id', '=', 'agg.category_id'))
            ->select(
                'categories.id as category_id',
                'categories.seq as seq',
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(categories.name, "$.zh_TW")) as category_name')
            );

        // ✅ 使用統一的欄位選取方法
        $this->applyAggregationSelects($query, 'agg');

        // ✅ 使用統一的排序方法
        $this->applySortLogic($query, $sortColumn, $sortDirection, 'agg');

        // ✅ 使用統一的格式化方法
        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn($row) => $this->formatStatsRow($row));
    }

    /**
     * 獲取影音主分類統計（分頁）
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件（包含 start_date、end_date）
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateDramaMainCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        // ✅ 使用統一的子查詢建立方法
        $subQuery = $this->buildAggregationSubQuery('drama', $startDate, $endDate);

        // 主查詢：LEFT JOIN 子查詢結果（只取主分類）
        $query = Category::where('type', 'drama')
            ->where('status', 1)
            ->whereNull('parent_id')  // 只取主分類
            ->leftJoinSub($subQuery, 'agg', fn($join) => $join->on('categories.id', '=', 'agg.category_id'))
            ->select(
                'categories.id as category_id',
                'categories.seq as seq',
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(categories.name, "$.zh_TW")) as category_name')
            );

        // ✅ 使用統一的欄位選取方法
        $this->applyAggregationSelects($query, 'agg');

        // ✅ 使用統一的排序方法
        $this->applySortLogic($query, $sortColumn, $sortDirection, 'agg');

        // ✅ 使用統一的格式化方法
        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn($row) => $this->formatStatsRow($row));
    }

    /**
     * 獲取子分類統計（分頁）- 統一方法
     *
     * @param string $contentType 內容類型（drama/program）
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件（包含 start_date、end_date、parent_category_id）
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    private function paginateSubCategories(string $contentType, $perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        $parentCategoryId = $filters['parent_category_id'] ?? null;

        // ✅ 子分類統計：使用 subcategory_id 分組的子查詢
        $selectRaw = [];
        foreach (AnalyticsField::all() as $field) {
            $selectRaw[] = "SUM({$field}) as {$field}";
        }

        $subQuery = DB::table('category_aggregations')
            ->select('subcategory_id')
            ->selectRaw(implode(",\n                ", $selectRaw))
            ->where('content_type', $contentType)  // ✅ 使用參數
            ->where('period_type', 'daily')
            ->whereBetween('period_date', [$startDate, $endDate])
            ->whereNotNull('subcategory_id');  // 只查詢有子分類 ID 的記錄

        // 如果有指定主分類，加入 category_id 篩選
        if ($parentCategoryId) {
            $subQuery->where('category_id', $parentCategoryId);
        }

        $subQuery->groupBy('subcategory_id');

        // 主查詢：先 JOIN 子查詢，再套用篩選條件
        $baseQuery = DB::table('categories as c')
            ->join('categories as p', 'c.parent_id', '=', 'p.id')
            ->leftJoinSub($subQuery, 'agg', fn($join) => $join->on('c.id', '=', 'agg.subcategory_id'))
            ->where('c.type', $contentType)  // ✅ 使用參數
            ->where('c.status', 1)
            ->whereNotNull('c.parent_id');  // 只取子分類

        // ✅ 關鍵修正：如果有指定主分類，篩選該主分類的子分類（必須在 JOIN 之後）
        if ($parentCategoryId) {
            $baseQuery->where('c.parent_id', $parentCategoryId);
        }

        $query = $baseQuery
            ->select(
                'c.id as category_id',
                'c.seq as seq',
                DB::raw('CONCAT(
                    JSON_UNQUOTE(JSON_EXTRACT(p.name, "$.zh_TW")),
                    " - ",
                    JSON_UNQUOTE(JSON_EXTRACT(c.name, "$.zh_TW"))
                ) as category_name')
            );

        // ✅ 使用統一的欄位選取方法（注意：DB Query Builder 需要手動處理）
        $selectRaw = [];
        foreach (AnalyticsField::all() as $field) {
            $selectRaw[] = "COALESCE(agg.{$field}, 0) as {$field}";
        }
        $query->selectRaw(implode(",\n                ", $selectRaw));

        // ✅ 使用統一的排序邏輯（但 DB Query Builder 無法用 Eloquent 方法）
        if (AnalyticsField::isNumericField($sortColumn)) {
            // 數值欄位排序：使用 CAST 確保數值排序而非字串排序
            $query->orderByRaw("CAST(COALESCE(agg.{$sortColumn}, 0) AS UNSIGNED) {$sortDirection}");
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // ✅ 使用統一的格式化方法
        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn($row) => $this->formatStatsRow($row));
    }

    /**
     * 獲取節目主分類統計（分頁）
     */
    public function paginateProgramMainCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        // ✅ 使用統一的子查詢建立方法
        $subQuery = $this->buildAggregationSubQuery('program', $startDate, $endDate);

        // 主查詢：LEFT JOIN 子查詢結果（只取主分類）
        $query = Category::where('type', 'program')
            ->where('status', 1)
            ->whereNull('parent_id')  // 只取主分類
            ->leftJoinSub($subQuery, 'agg', fn($join) => $join->on('categories.id', '=', 'agg.category_id'))
            ->select(
                'categories.id as category_id',
                'categories.seq as seq',
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(categories.name, "$.zh_TW")) as category_name')
            );

        // ✅ 使用統一的欄位選取方法
        $this->applyAggregationSelects($query, 'agg');

        // ✅ 使用統一的排序方法
        $this->applySortLogic($query, $sortColumn, $sortDirection, 'agg');

        // ✅ 使用統一的格式化方法
        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn($row) => $this->formatStatsRow($row));
    }

    /**
     * 獲取影音子分類統計（分頁）- 公開包裝方法
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件（包含 start_date、end_date、parent_category_id）
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateDramaSubCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        return $this->paginateSubCategories('drama', $perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 獲取節目子分類統計（分頁）- 公開包裝方法
     *
     * @param int $perPage 每頁筆數
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件（包含 start_date、end_date、parent_category_id）
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateProgramSubCategories($perPage, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        return $this->paginateSubCategories('program', $perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 獲取主分類清單（用於子分類搜尋篩選）
     *
     * @param string $type 分類類型（drama/program）
     * @return array
     */
    public function getMainCategoriesForFilter(string $type): array
    {
        return Category::where('type', $type)
            ->where('status', 1)
            ->whereNull('parent_id')  // 只取主分類
            ->orderBy('seq')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->getTranslation('name', 'zh_TW'),
                ];
            })
            ->toArray();
    }

}
