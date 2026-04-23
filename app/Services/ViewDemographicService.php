<?php

namespace App\Services;

use App\Repositories\ViewDemographicRepository;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ViewDemographicService
{
    protected $repository;

    public function __construct(ViewDemographicRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 每日聚合主邏輯（Command 呼叫）- 增量更新模式
     *
     * @param string|null $date 日期（Y-m-d 格式），null = 昨天
     * @return array
     */
    public function aggregateDailyDemographics(?string $date = null): array
    {
        try {
            // 預設聚合昨天的資料
            $targetDate = $date ?? Carbon::yesterday()->format('Y-m-d');

            Log::channel('daily')->info('開始每日人口統計聚合（增量模式）', [
                'date' => $targetDate,
                'started_at' => now(),
            ]);

            // 執行聚合
            $result = $this->repository->aggregateFromViewLogs($targetDate);

            Log::channel('daily')->info('每日人口統計聚合完成（增量模式）', [
                'date' => $targetDate,
                'result' => $result,
                'completed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "成功聚合 {$targetDate} 的人口統計資料",
                'data' => $result,
            ];

        } catch (\Exception $e) {
            Log::channel('daily')->error('每日人口統計聚合失敗', [
                'date' => $targetDate ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '每日人口統計聚合失敗：' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 全量重算所有歷史資料（週日執行）
     *
     * @return array
     */
    public function recalculateAllDemographics(): array
    {
        try {
            Log::channel('daily')->info('開始全量重算人口統計', [
                'started_at' => now(),
            ]);

            // 執行全量重算
            $result = $this->repository->recalculateAllFromViewLogs();

            Log::channel('daily')->info('全量重算人口統計完成', [
                'result' => $result,
                'completed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "成功重算所有歷史人口統計資料",
                'data' => $result,
            ];

        } catch (\Exception $e) {
            Log::channel('daily')->error('全量重算人口統計失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '全量重算人口統計失敗：' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * 計算年齡區間
     * 
     * @param string $birthdate
     * @return string
     */
    public function calculateAgeGroup(string $birthdate): string
    {
        $age = Carbon::parse($birthdate)->age;

        if ($age <= 10) return 'age_0_10';
        if ($age <= 20) return 'age_11_20';
        if ($age <= 30) return 'age_21_30';
        if ($age <= 40) return 'age_31_40';
        if ($age <= 50) return 'age_41_50';
        if ($age <= 60) return 'age_51_60';
        return 'age_61_plus';
    }

    /**
     * 計算性別統計（只有 male/female）
     * 
     * @param string $gender
     * @return string|null
     */
    public function calculateGenderStats(string $gender): ?string
    {
        // 只統計 male 和 female
        if ($gender === 'male') return 'male';
        if ($gender === 'female') return 'female';
        
        return null; // 其他值不計入統計
    }
}
