<?php

namespace App\Services;

use App\Repositories\CategoryAggregationRepository;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CategoryAggregationService
{
    protected $repository;

    public function __construct(CategoryAggregationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 分類聚合主邏輯（Command 呼叫）- 增量更新模式
     *
     * @param string|null $date 日期（Y-m-d 格式），null = 昨天
     * @param bool $includeWeekly 是否包含週統計（週一執行）
     * @param bool $includeMonthly 是否包含月統計（月初執行）
     * @return array
     */
    public function aggregateCategoryStats(
        ?string $date = null,
        bool $includeWeekly = false,
        bool $includeMonthly = false
    ): array {
        try {
            // 預設聚合昨天的資料
            $targetDate = $date ?? Carbon::yesterday()->format('Y-m-d');

            Log::channel('daily')->info('開始分類聚合（增量模式）', [
                'date' => $targetDate,
                'include_weekly' => $includeWeekly,
                'include_monthly' => $includeMonthly,
                'started_at' => now(),
            ]);

            $results = [];
            $contentTypes = ['drama', 'program', 'article', 'live', 'radio'];

            // 對每種內容類型進行聚合
            foreach ($contentTypes as $contentType) {
                // Daily 聚合
                $dailyResult = $this->aggregateByPeriod($contentType, 'daily', $targetDate);
                $results[$contentType]['daily'] = $dailyResult;

                // Weekly 聚合（週一執行）
                if ($includeWeekly) {
                    $weekStart = Carbon::parse($targetDate)->startOfWeek()->format('Y-m-d');
                    $weeklyResult = $this->aggregateByPeriod($contentType, 'weekly', $weekStart);
                    $results[$contentType]['weekly'] = $weeklyResult;
                }

                // Monthly 聚合（月初執行）
                if ($includeMonthly) {
                    $monthStart = Carbon::parse($targetDate)->startOfMonth()->format('Y-m-d');
                    $monthlyResult = $this->aggregateByPeriod($contentType, 'monthly', $monthStart);
                    $results[$contentType]['monthly'] = $monthlyResult;
                }

                // All Time 聚合（每次都執行）
                $allTimeResult = $this->aggregateByPeriod($contentType, 'all_time', null);
                $results[$contentType]['all_time'] = $allTimeResult;
            }

            Log::channel('daily')->info('分類聚合完成（增量模式）', [
                'date' => $targetDate,
                'results' => $results,
                'completed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "成功聚合 {$targetDate} 的分類統計資料",
                'data' => $results,
            ];

        } catch (\Exception $e) {
            Log::channel('daily')->error('分類聚合失敗', [
                'date' => $targetDate ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '分類聚合失敗：' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 全量重算所有週期統計（週日執行）
     *
     * @return array
     */
    public function recalculateAllCategoryStats(): array
    {
        try {
            Log::channel('daily')->info('開始全量重算分類統計', [
                'started_at' => now(),
            ]);

            // 執行全量重算
            $result = $this->repository->recalculateAllFromDemographics();

            Log::channel('daily')->info('全量重算分類統計完成', [
                'result' => $result,
                'completed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "成功重算所有分類統計資料",
                'data' => $result,
            ];

        } catch (\Exception $e) {
            Log::channel('daily')->error('全量重算分類統計失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '全量重算分類統計失敗：' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 依時間週期聚合
     * 
     * @param string $contentType
     * @param string $periodType (daily/weekly/monthly/all_time)
     * @param string|null $periodDate
     * @return array
     */
    public function aggregateByPeriod(string $contentType, string $periodType, ?string $periodDate): array
    {
        return $this->repository->aggregateFromDemographics($contentType, $periodType, $periodDate);
    }
}
