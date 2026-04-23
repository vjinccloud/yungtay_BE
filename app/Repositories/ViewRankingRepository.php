<?php

namespace App\Repositories;

use App\Models\ViewRanking;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ViewRankingRepository extends BaseRepository
{
    public function __construct(ViewRanking $model)
    {
        parent::__construct($model);
    }

    /**
     * 建立或更新排行榜記錄
     */
    public function createOrUpdateRanking(array $data): ViewRanking
    {
        $conditions = [
            'period_type' => $data['period_type'],
            'period_date' => $data['period_date'],
            'content_type' => $data['content_type'],
            'content_id' => $data['content_id']
        ];

        return $this->model->updateOrCreate($conditions, $data);
    }

    /**
     * 批量建立排行榜（從統計數據生成）
     */
    public function generateRankingsFromStats(string $periodType, string $periodDate, string $contentType, int $limit = 100): int
    {
        // 先清除該期間的舊排行榜
        $this->clearPeriodRankings($periodType, $periodDate, $contentType);

        $statisticRepo = app(\App\Repositories\ViewStatisticRepository::class);
        
        // 根據期間類型決定統計欄位
        $orderBy = match($periodType) {
            'daily' => 'daily_views',
            default => 'total_views'
        };

        // 取得排行數據
        $topContent = $statisticRepo->getPopularContent($contentType, $limit, $orderBy);
        
        $createdCount = 0;
        $ranking = 1;

        foreach ($topContent as $content) {
            // 計算成長率（與前一期間比較）
            $growthRate = $this->calculateGrowthRate($content, $periodType, $periodDate);

            $this->createOrUpdateRanking([
                'period_type' => $periodType,
                'period_date' => $periodDate,
                'content_type' => $contentType,
                'content_id' => $content->content_id,
                'ranking' => $ranking,
                'view_count' => $content->$orderBy,
                'unique_count' => $content->unique_views,
                'growth_rate' => $growthRate
            ]);

            $ranking++;
            $createdCount++;
        }

        return $createdCount;
    }

    /**
     * 計算成長率
     */
    private function calculateGrowthRate($content, string $periodType, string $periodDate): float
    {
        try {
            $previousDate = $this->getPreviousPeriodDate($periodType, $periodDate);
            
            $previousRanking = $this->model->newQuery()
                ->where('period_type', $periodType)
                ->where('period_date', $previousDate)
                ->where('content_type', $content->content_type)
                ->where('content_id', $content->content_id)
                ->first();

            if (!$previousRanking) {
                return 0; // 新內容，無法計算成長率
            }

            $currentViews = $content->total_views;
            $previousViews = $previousRanking->view_count;

            if ($previousViews == 0) {
                return $currentViews > 0 ? 100.0 : 0.0;
            }

            return round((($currentViews - $previousViews) / $previousViews) * 100, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 取得前一期間的日期
     */
    private function getPreviousPeriodDate(string $periodType, string $periodDate): string
    {
        $date = Carbon::parse($periodDate);

        return match($periodType) {
            'daily' => $date->subDay()->format('Y-m-d'),
            'weekly' => $date->subWeek()->format('Y-m-d'),
            'monthly' => $date->subMonth()->format('Y-m-d'),
            'yearly' => $date->subYear()->format('Y-m-d'),
            default => $date->subDay()->format('Y-m-d')
        };
    }

    /**
     * 清除指定期間的排行榜
     */
    public function clearPeriodRankings(string $periodType, string $periodDate, string $contentType = null): int
    {
        $query = $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate);

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        return $query->delete();
    }

    /**
     * 取得排行榜
     */
    public function getRankings(string $periodType, string $periodDate = null, string $contentType = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $periodDate = $periodDate ?: $this->getCurrentPeriodDate($periodType);

        $query = $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->with(['drama:id,title', 'program:id,title', 'article:id,title', 'live:id,title', 'radio:id,title'])
            ->orderBy('ranking', 'asc')
            ->limit($limit);

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        return $query->get();
    }

    /**
     * 取得當前期間日期
     */
    private function getCurrentPeriodDate(string $periodType): string
    {
        return match($periodType) {
            'daily' => now()->format('Y-m-d'),
            'weekly' => now()->startOfWeek()->format('Y-m-d'),
            'monthly' => now()->startOfMonth()->format('Y-m-d'),
            'yearly' => now()->startOfYear()->format('Y-m-d'),
            default => now()->format('Y-m-d')
        };
    }

    /**
     * 取得跨類型綜合排行榜
     */
    public function getCrossTypeRankings(string $periodType, string $periodDate = null, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $periodDate = $periodDate ?: $this->getCurrentPeriodDate($periodType);

        return $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->with(['drama:id,title', 'program:id,title', 'article:id,title', 'live:id,title', 'radio:id,title'])
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item, $index) {
                $item->cross_ranking = $index + 1;
                return $item;
            });
    }

    /**
     * 取得內容的歷史排名
     */
    public function getContentRankingHistory(string $contentType, int $contentId, string $periodType, int $periods = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->where('period_type', $periodType)
            ->orderBy('period_date', 'desc')
            ->limit($periods)
            ->get();
    }

    /**
     * 取得上升最快的內容
     */
    public function getFastestRising(string $periodType, string $periodDate = null, string $contentType = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $periodDate = $periodDate ?: $this->getCurrentPeriodDate($periodType);

        $query = $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->where('growth_rate', '>', 0)
            ->with(['drama:id,title', 'program:id,title', 'article:id,title', 'live:id,title', 'radio:id,title'])
            ->orderBy('growth_rate', 'desc')
            ->limit($limit);

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        return $query->get();
    }

    /**
     * 取得新進榜內容
     */
    public function getNewEntries(string $periodType, string $periodDate = null, string $contentType = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $periodDate = $periodDate ?: $this->getCurrentPeriodDate($periodType);
        $previousDate = $this->getPreviousPeriodDate($periodType, $periodDate);

        // 找出本期有排名但上期沒有的內容
        $currentIds = $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->when($contentType, fn($q) => $q->where('content_type', $contentType))
            ->pluck('content_id');

        $previousIds = $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $previousDate)
            ->when($contentType, fn($q) => $q->where('content_type', $contentType))
            ->pluck('content_id');

        $newEntryIds = $currentIds->diff($previousIds);

        if ($newEntryIds->isEmpty()) {
            return collect();
        }

        return $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->whereIn('content_id', $newEntryIds)
            ->when($contentType, fn($q) => $q->where('content_type', $contentType))
            ->with(['drama:id,title', 'program:id,title', 'article:id,title', 'live:id,title', 'radio:id,title'])
            ->orderBy('ranking', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * 取得排行榜統計摘要
     */
    public function getRankingSummary(string $periodType, string $periodDate = null): array
    {
        $periodDate = $periodDate ?: $this->getCurrentPeriodDate($periodType);

        $stats = $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->selectRaw('
                content_type,
                COUNT(*) as count,
                SUM(view_count) as total_views,
                AVG(view_count) as avg_views,
                MAX(view_count) as max_views,
                MIN(view_count) as min_views
            ')
            ->groupBy('content_type')
            ->get()
            ->keyBy('content_type');

        $overall = $this->model->newQuery()
            ->where('period_type', $periodType)
            ->where('period_date', $periodDate)
            ->selectRaw('
                COUNT(*) as total_entries,
                SUM(view_count) as total_views,
                AVG(growth_rate) as avg_growth_rate
            ')
            ->first();

        return [
            'overall' => [
                'total_entries' => (int) $overall->total_entries,
                'total_views' => (int) $overall->total_views,
                'avg_growth_rate' => round($overall->avg_growth_rate, 2)
            ],
            'by_type' => $stats->mapWithKeys(function ($item, $key) {
                return [$key => [
                    'count' => (int) $item->count,
                    'total_views' => (int) $item->total_views,
                    'avg_views' => round($item->avg_views, 0),
                    'max_views' => (int) $item->max_views,
                    'min_views' => (int) $item->min_views
                ]];
            })->toArray()
        ];
    }

    /**
     * 清理舊排行榜記錄
     */
    public function cleanOldRankings(int $daysToKeep = 365): int
    {
        return $this->model->newQuery()
            ->where('period_date', '<', now()->subDays($daysToKeep)->format('Y-m-d'))
            ->delete();
    }

    /**
     * 分頁查詢排行榜記錄
     */
    public function paginate($perPage = 15, $sortColumn = 'ranking', $sortDirection = 'asc', $filters = [])
    {
        $query = $this->model->newQuery()
            ->with(['drama:id,title', 'program:id,title', 'article:id,title', 'live:id,title', 'radio:id,title']);

        // 應用篩選條件
        if (!empty($filters['period_type'])) {
            $query->where('period_type', $filters['period_type']);
        }

        if (!empty($filters['period_date'])) {
            $query->whereDate('period_date', $filters['period_date']);
        }

        if (!empty($filters['content_type'])) {
            $query->where('content_type', $filters['content_type']);
        }

        if (!empty($filters['min_ranking'])) {
            $query->where('ranking', '>=', $filters['min_ranking']);
        }

        if (!empty($filters['max_ranking'])) {
            $query->where('ranking', '<=', $filters['max_ranking']);
        }

        if (isset($filters['positive_growth']) && $filters['positive_growth']) {
            $query->where('growth_rate', '>', 0);
        }

        // 排序
        $allowedSortColumns = ['ranking', 'view_count', 'growth_rate', 'period_date'];
        if ($sortColumn && $sortColumn !== 'null' && in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection === 'null' ? 'asc' : $sortDirection);
        } else {
            $query->orderBy('ranking', 'asc');
        }

        return $query->paginate($perPage);
    }
}