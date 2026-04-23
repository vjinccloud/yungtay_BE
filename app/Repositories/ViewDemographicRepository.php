<?php

namespace App\Repositories;

use App\Models\ViewDemographic;
use App\Models\ViewLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ViewDemographicRepository
{
    protected $model;

    public function __construct(ViewDemographic $model)
    {
        $this->model = $model;
    }

    /**
     * 從 view_logs 聚合資料到 view_demographics（增量更新模式）
     *
     * @param string $date 日期（Y-m-d 格式）
     * @return array 聚合結果統計
     */
    public function aggregateFromViewLogs(string $date): array
    {
        $targetDate = Carbon::parse($date)->format('Y-m-d');

        // 查詢該日期的所有觀看記錄
        $viewLogs = ViewLog::whereDate('created_at', $targetDate)
            ->with('user') // 載入會員資料（用於性別和年齡統計）
            ->get();

        // 按 content_type, content_id, episode_id 分組
        $grouped = $viewLogs->groupBy(function ($log) {
            return sprintf(
                '%s|%d|%d',
                $log->content_type,
                $log->content_id,
                $log->episode_id ?? 0 // 沒有集數的使用 0
            );
        });

        $insertedCount = 0;
        $updatedCount = 0;

        foreach ($grouped as $key => $logs) {
            list($contentType, $contentId, $episodeId) = explode('|', $key);

            // 取得 category_id 和 subcategory_id（從第一筆 log 的關聯內容取得）
            $categoryIds = $this->getCategoryIds($contentType, (int)$contentId);

            // 計算統計資料
            $stats = $this->calculateStats($logs, $targetDate);
            $stats['content_type'] = $contentType;
            $stats['content_id'] = (int)$contentId;
            $stats['episode_id'] = (int)$episodeId;
            $stats['category_id'] = $categoryIds['category_id'];
            $stats['subcategory_id'] = $categoryIds['subcategory_id'];
            $stats['date'] = $targetDate;

            // 使用 UPSERT 插入或更新
            $result = $this->upsertDemographics($stats);

            if ($result === 'inserted') {
                $insertedCount++;
            } else {
                $updatedCount++;
            }
        }

        return [
            'date' => $targetDate,
            'total_groups' => $grouped->count(),
            'inserted' => $insertedCount,
            'updated' => $updatedCount,
        ];
    }

    /**
     * 從 view_logs 重新計算全部歷史資料（全量重算模式）
     *
     * @return array 聚合結果統計
     */
    public function recalculateAllFromViewLogs(): array
    {
        try {
            // 1. 清空 view_demographics 表
            $this->model->truncate();

            // 2. 取得所有 view_logs 的日期範圍（排除今天，避免不完整資料）
            $dates = ViewLog::selectRaw('DISTINCT DATE(created_at) as date')
                ->whereDate('created_at', '<', now()->startOfDay())  // 排除今天
                ->orderBy('date')
                ->pluck('date');

            $totalDates = $dates->count();
            $totalGroups = 0;
            $totalRecords = 0;

            // 3. 逐日聚合
            foreach ($dates as $date) {
                $result = $this->aggregateFromViewLogs($date);
                $totalGroups += $result['total_groups'];
                $totalRecords += ($result['inserted'] + $result['updated']);
            }

            return [
                'total_dates' => $totalDates,
                'total_groups' => $totalGroups,
                'total_records' => $totalRecords,
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 計算統計資料
     * 
     * @param \Illuminate\Support\Collection $logs
     * @param string $date
     * @return array
     */
    protected function calculateStats($logs, string $date): array
    {
        $stats = [
            // 基礎統計
            'total_views' => $logs->count(),
            'unique_views' => $logs->unique('ip')->count(),
            'member_views' => $logs->whereNotNull('user_id')->count(),
            'guest_views' => $logs->whereNull('user_id')->count(),
            
            // 性別統計
            'male_views' => 0,
            'female_views' => 0,
            
            // 年齡統計（7個區間）
            'age_0_10' => 0,
            'age_11_20' => 0,
            'age_21_30' => 0,
            'age_31_40' => 0,
            'age_41_50' => 0,
            'age_51_60' => 0,
            'age_61_plus' => 0,
            
            // 男性年齡交叉統計（7個）
            'male_age_0_10' => 0,
            'male_age_11_20' => 0,
            'male_age_21_30' => 0,
            'male_age_31_40' => 0,
            'male_age_41_50' => 0,
            'male_age_51_60' => 0,
            'male_age_61_plus' => 0,
            
            // 女性年齡交叉統計（7個）
            'female_age_0_10' => 0,
            'female_age_11_20' => 0,
            'female_age_21_30' => 0,
            'female_age_31_40' => 0,
            'female_age_41_50' => 0,
            'female_age_51_60' => 0,
            'female_age_61_plus' => 0,
        ];
        
        // 只統計會員的性別和年齡
        $memberLogs = $logs->whereNotNull('user_id');
        
        foreach ($memberLogs as $log) {
            $user = $log->user;
            if (!$user) continue;
            
            // 性別統計
            if ($user->gender === 'male') {
                $stats['male_views']++;
            } elseif ($user->gender === 'female') {
                $stats['female_views']++;
            }
            
            // 年齡統計
            if ($user->birthdate) {
                $ageGroup = $this->getAgeGroup($user->birthdate);
                $stats[$ageGroup]++;
                
                // 交叉統計（性別×年齡）
                if ($user->gender === 'male') {
                    $stats['male_' . $ageGroup]++;
                } elseif ($user->gender === 'female') {
                    $stats['female_' . $ageGroup]++;
                }
            }
        }
        
        return $stats;
    }

    /**
     * 計算年齡區間
     * 
     * @param string $birthdate
     * @return string
     */
    protected function getAgeGroup(string $birthdate): string
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
     * 根據內容類型和ID取得 category_id 和 subcategory_id
     *
     * @param string $contentType
     * @param int $contentId
     * @return array ['category_id' => int|null, 'subcategory_id' => int|null]
     */
    protected function getCategoryIds(string $contentType, int $contentId): array
    {
        $modelMap = [
            'drama' => \App\Models\Drama::class,
            'program' => \App\Models\Program::class,
            'article' => \App\Models\Article::class,
            'live' => \App\Models\Live::class,
            'radio' => \App\Models\Radio::class,
        ];

        // 預設回傳值
        $result = [
            'category_id' => null,
            'subcategory_id' => null,
        ];

        if (!isset($modelMap[$contentType])) {
            return $result;
        }

        $model = $modelMap[$contentType];
        $content = $model::find($contentId);

        if (!$content) {
            return $result;
        }

        $result['category_id'] = $content->category_id;

        // 只有 drama 和 program 有 subcategory_id
        if (in_array($contentType, ['drama', 'program']) && isset($content->subcategory_id)) {
            $result['subcategory_id'] = $content->subcategory_id;
        }

        return $result;
    }

    /**
     * 插入或更新統計資料（UPSERT）
     *
     * @param array $data
     * @return string 'inserted' 或 'updated'
     */
    public function upsertDemographics(array $data): string
    {
        $existing = $this->model
            ->where('content_type', $data['content_type'])
            ->where('content_id', $data['content_id'])
            ->where('episode_id', $data['episode_id'])
            ->where('date', $data['date'])
            ->first();

        if ($existing) {
            $existing->update($data);
            return 'updated';
        } else {
            $this->model->create($data);
            return 'inserted';
        }
    }
}
