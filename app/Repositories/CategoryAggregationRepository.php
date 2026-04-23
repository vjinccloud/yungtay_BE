<?php

namespace App\Repositories;

use App\Models\CategoryAggregation;
use App\Models\ViewDemographic;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategoryAggregationRepository
{
    protected $model;

    public function __construct(CategoryAggregation $model)
    {
        $this->model = $model;
    }

    /**
     * 從 view_demographics 聚合資料到分類層級
     *
     * @param string $contentType 內容類型（drama/program/article/live/radio）
     * @param string $periodType 週期類型（daily/weekly/monthly/all_time）
     * @param string|null $periodDate 週期日期（daily/weekly/monthly 需要，all_time 不需要）
     * @return array 聚合結果統計
     */
    public function aggregateFromDemographics(
        string $contentType,
        string $periodType,
        ?string $periodDate = null
    ): array {
        // 建立查詢（直接使用 view_demographics 的 category_id 和 subcategory_id，不需要 JOIN）
        $query = ViewDemographic::select([
            'category_id',
            'subcategory_id',
            DB::raw('SUM(total_views) as total_views'),
            DB::raw('SUM(unique_views) as unique_views'),
            DB::raw('SUM(member_views) as member_views'),
            DB::raw('SUM(guest_views) as guest_views'),
            DB::raw('SUM(male_views) as male_views'),
            DB::raw('SUM(female_views) as female_views'),
            DB::raw('SUM(age_0_10) as age_0_10'),
            DB::raw('SUM(age_11_20) as age_11_20'),
            DB::raw('SUM(age_21_30) as age_21_30'),
            DB::raw('SUM(age_31_40) as age_31_40'),
            DB::raw('SUM(age_41_50) as age_41_50'),
            DB::raw('SUM(age_51_60) as age_51_60'),
            DB::raw('SUM(age_61_plus) as age_61_plus'),
            DB::raw('SUM(male_age_0_10) as male_age_0_10'),
            DB::raw('SUM(male_age_11_20) as male_age_11_20'),
            DB::raw('SUM(male_age_21_30) as male_age_21_30'),
            DB::raw('SUM(male_age_31_40) as male_age_31_40'),
            DB::raw('SUM(male_age_41_50) as male_age_41_50'),
            DB::raw('SUM(male_age_51_60) as male_age_51_60'),
            DB::raw('SUM(male_age_61_plus) as male_age_61_plus'),
            DB::raw('SUM(female_age_0_10) as female_age_0_10'),
            DB::raw('SUM(female_age_11_20) as female_age_11_20'),
            DB::raw('SUM(female_age_21_30) as female_age_21_30'),
            DB::raw('SUM(female_age_31_40) as female_age_31_40'),
            DB::raw('SUM(female_age_41_50) as female_age_41_50'),
            DB::raw('SUM(female_age_51_60) as female_age_51_60'),
            DB::raw('SUM(female_age_61_plus) as female_age_61_plus'),
        ])
        ->where('content_type', $contentType)
        ->whereNotNull('category_id');

        // 根據週期類型篩選日期範圍
        $query = $this->applyDateFilter($query, $periodType, $periodDate);

        // 按分類分組（包含 category_id 和 subcategory_id）
        $results = $query->groupBy(['category_id', 'subcategory_id'])->get();

        $insertedCount = 0;
        $updatedCount = 0;

        foreach ($results as $result) {
            $data = [
                'content_type' => $contentType,
                'category_id' => $result->category_id,
                'subcategory_id' => $result->subcategory_id,  // 新增：子分類 ID
                'period_type' => $periodType,
                'period_date' => $periodDate,
                'total_views' => $result->total_views ?? 0,
                'unique_views' => $result->unique_views ?? 0,
                'member_views' => $result->member_views ?? 0,
                'guest_views' => $result->guest_views ?? 0,
                'male_views' => $result->male_views ?? 0,
                'female_views' => $result->female_views ?? 0,
                'age_0_10' => $result->age_0_10 ?? 0,
                'age_11_20' => $result->age_11_20 ?? 0,
                'age_21_30' => $result->age_21_30 ?? 0,
                'age_31_40' => $result->age_31_40 ?? 0,
                'age_41_50' => $result->age_41_50 ?? 0,
                'age_51_60' => $result->age_51_60 ?? 0,
                'age_61_plus' => $result->age_61_plus ?? 0,
                'male_age_0_10' => $result->male_age_0_10 ?? 0,
                'male_age_11_20' => $result->male_age_11_20 ?? 0,
                'male_age_21_30' => $result->male_age_21_30 ?? 0,
                'male_age_31_40' => $result->male_age_31_40 ?? 0,
                'male_age_41_50' => $result->male_age_41_50 ?? 0,
                'male_age_51_60' => $result->male_age_51_60 ?? 0,
                'male_age_61_plus' => $result->male_age_61_plus ?? 0,
                'female_age_0_10' => $result->female_age_0_10 ?? 0,
                'female_age_11_20' => $result->female_age_11_20 ?? 0,
                'female_age_21_30' => $result->female_age_21_30 ?? 0,
                'female_age_31_40' => $result->female_age_31_40 ?? 0,
                'female_age_41_50' => $result->female_age_41_50 ?? 0,
                'female_age_51_60' => $result->female_age_51_60 ?? 0,
                'female_age_61_plus' => $result->female_age_61_plus ?? 0,
            ];

            $upsertResult = $this->upsertAggregation($data);

            if ($upsertResult === 'inserted') {
                $insertedCount++;
            } else {
                $updatedCount++;
            }
        }

        return [
            'content_type' => $contentType,
            'period_type' => $periodType,
            'period_date' => $periodDate,
            'total_categories' => $results->count(),
            'inserted' => $insertedCount,
            'updated' => $updatedCount,
        ];
    }

    /**
     * 根據週期類型套用日期篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $periodType
     * @param string|null $periodDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyDateFilter($query, string $periodType, ?string $periodDate)
    {
        switch ($periodType) {
            case 'daily':
                // 當日資料
                $query->whereDate('date', $periodDate);
                break;

            case 'weekly':
                // 當週資料（週一到週日）
                $startOfWeek = Carbon::parse($periodDate)->startOfWeek();
                $endOfWeek = Carbon::parse($periodDate)->endOfWeek();
                $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
                break;

            case 'monthly':
                // 當月資料
                $startOfMonth = Carbon::parse($periodDate)->startOfMonth();
                $endOfMonth = Carbon::parse($periodDate)->endOfMonth();
                $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
                break;

            case 'all_time':
                // 全時段資料（不篩選日期）
                break;
        }

        return $query;
    }

    /**
     * 插入或更新分類統計資料（UPSERT）
     *
     * @param array $data
     * @return string 'inserted' 或 'updated'
     */
    public function upsertAggregation(array $data): string
    {
        $query = $this->model
            ->where('content_type', $data['content_type'])
            ->where('category_id', $data['category_id'])
            ->where('period_type', $data['period_type'])
            ->where('period_date', $data['period_date']);

        // subcategory_id 可能為 NULL，需要特殊處理
        if (isset($data['subcategory_id']) && $data['subcategory_id'] !== null) {
            $query->where('subcategory_id', $data['subcategory_id']);
        } else {
            $query->whereNull('subcategory_id');
        }

        $existing = $query->first();

        if ($existing) {
            $existing->update($data);
            return 'updated';
        } else {
            $this->model->create($data);
            return 'inserted';
        }
    }

    /**
     * 從 view_demographics 重新計算全部週期統計（全量重算模式）
     *
     * @return array 聚合結果統計
     */
    public function recalculateAllFromDemographics(): array
    {
        try {
            // 1. 清空 category_aggregations 表
            $this->model->truncate();

            $contentTypes = ['drama', 'program', 'article', 'live', 'radio'];
            $totalCategories = 0;
            $totalRecords = 0;

            // 2. 對每種內容類型重新聚合全部週期
            foreach ($contentTypes as $contentType) {
                // Daily: 取得所有日期並逐日聚合
                $dates = ViewDemographic::select('date')
                    ->where('content_type', $contentType)
                    ->whereNotNull('category_id')
                    ->distinct()
                    ->orderBy('date')
                    ->pluck('date');

                foreach ($dates as $date) {
                    $result = $this->aggregateFromDemographics($contentType, 'daily', $date);
                    $totalCategories += $result['total_categories'];
                    $totalRecords += ($result['inserted'] + $result['updated']);
                }

                // Weekly: 取得所有週一日期並逐週聚合
                $weekStarts = ViewDemographic::selectRaw('DATE(DATE_SUB(date, INTERVAL WEEKDAY(date) DAY)) as week_start')
                    ->where('content_type', $contentType)
                    ->whereNotNull('category_id')
                    ->distinct()
                    ->orderBy('week_start')
                    ->pluck('week_start');

                foreach ($weekStarts as $weekStart) {
                    $result = $this->aggregateFromDemographics($contentType, 'weekly', $weekStart);
                    $totalCategories += $result['total_categories'];
                    $totalRecords += ($result['inserted'] + $result['updated']);
                }

                // Monthly: 取得所有月份並逐月聚合
                $months = ViewDemographic::selectRaw('DATE_FORMAT(date, "%Y-%m-01") as month_start')
                    ->where('content_type', $contentType)
                    ->whereNotNull('category_id')
                    ->distinct()
                    ->orderBy('month_start')
                    ->pluck('month_start');

                foreach ($months as $month) {
                    $result = $this->aggregateFromDemographics($contentType, 'monthly', $month);
                    $totalCategories += $result['total_categories'];
                    $totalRecords += ($result['inserted'] + $result['updated']);
                }

                // All Time: 全時段聚合
                $result = $this->aggregateFromDemographics($contentType, 'all_time', null);
                $totalCategories += $result['total_categories'];
                $totalRecords += ($result['inserted'] + $result['updated']);
            }

            return [
                'total_content_types' => count($contentTypes),
                'total_categories' => $totalCategories,
                'total_records' => $totalRecords,
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }
}
