<?php

namespace App\Services;

use App\Repositories\ViewLogRepository;
use App\Repositories\ViewStatisticRepository;
use App\Repositories\ViewDemographicRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ViewAnalyticsService extends BaseService
{
    protected $viewLogRepository;
    protected $viewStatisticRepository;
    protected $viewDemographicRepository;
    
    // 快取設定
    protected $cachePrefix = 'sjtv:analytics:';
    protected $cacheExpire = 3600; // 1 小時

    public function __construct(ViewLogRepository $viewLogRepository)
    {
        parent::__construct($viewLogRepository);
        $this->viewLogRepository = $viewLogRepository;
        $this->viewStatisticRepository = app(ViewStatisticRepository::class);
        $this->viewDemographicRepository = app(ViewDemographicRepository::class);
    }

    /**
     * 取得年齡分布統計
     */
    public function getAgeDistribution(string $contentType, string $period = 'week'): array
    {
        $cacheKey = $this->cachePrefix . "age_dist:{$contentType}:{$period}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($contentType, $period) {
            $dateRange = $this->getDateRange($period);
            
            return $this->viewDemographicRepository->getAgeDistribution(
                $contentType,
                $dateRange['start'],
                $dateRange['end']
            );
        });
    }

    /**
     * 取得性別分布統計
     */
    public function getGenderDistribution(string $contentType, string $period = 'week'): array
    {
        $cacheKey = $this->cachePrefix . "gender_dist:{$contentType}:{$period}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($contentType, $period) {
            $dateRange = $this->getDateRange($period);
            
            return $this->viewDemographicRepository->getGenderDistribution(
                $contentType,
                $dateRange['start'],
                $dateRange['end']
            );
        });
    }

    /**
     * 取得會員訪客比例
     */
    public function getMemberGuestRatio(string $contentType, string $period = 'week'): array
    {
        $cacheKey = $this->cachePrefix . "member_guest:{$contentType}:{$period}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($contentType, $period) {
            $dateRange = $this->getDateRange($period);
            
            return $this->viewDemographicRepository->getMemberGuestRatio(
                $contentType,
                $dateRange['start'],
                $dateRange['end']
            );
        });
    }

    /**
     * 取得內容類型統計
     */
    public function getContentTypeStats(string $period = 'week'): array
    {
        $cacheKey = $this->cachePrefix . "content_types:{$period}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($period) {
            $dateRange = $this->getDateRange($period);
            
            $stats = $this->viewDemographicRepository->getContentTypeStats(
                $dateRange['start'],
                $dateRange['end']
            );

            return $stats->map(function ($item) {
                $totalViews = $item->total_views;
                
                return [
                    'content_type' => $item->content_type,
                    'total_views' => $totalViews,
                    'member_views' => $item->member_views,
                    'guest_views' => $item->guest_views,
                    'member_rate' => $totalViews > 0 ? round(($item->member_views / $totalViews) * 100, 2) : 0,
                    'guest_rate' => $totalViews > 0 ? round(($item->guest_views / $totalViews) * 100, 2) : 0,
                    'age_known_rate' => ($item->age_known + $item->age_unknown) > 0 
                        ? round(($item->age_known / ($item->age_known + $item->age_unknown)) * 100, 2) : 0,
                    'gender_known_rate' => ($item->gender_known + $item->gender_unknown) > 0 
                        ? round(($item->gender_known / ($item->gender_known + $item->gender_unknown)) * 100, 2) : 0,
                ];
            })->toArray();
        });
    }

    /**
     * 取得觀看趨勢
     */
    public function getViewTrend(string $contentType, int $contentId, int $days = 30, ?int $episodeId = null): array
    {
        $cacheKey = $this->cachePrefix . "trend:{$contentType}:{$contentId}:{$days}" . ($episodeId ? ":{$episodeId}" : '');
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($contentType, $contentId, $days, $episodeId) {
            $trend = $this->viewLogRepository->getViewTrend($contentType, $contentId, $days, $episodeId);
            
            // 補齊缺失的日期
            $result = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $found = $trend->firstWhere('date', $date);
                
                $result[] = [
                    'date' => $date,
                    'views' => $found ? (int) $found->views : 0,
                    'formatted_date' => Carbon::parse($date)->format('m/d')
                ];
            }
            
            return $result;
        });
    }

    /**
     * 取得熱門時段分析
     */
    public function getPopularTimeSlots(string $contentType = null, int $days = 7): array
    {
        $cacheKey = $this->cachePrefix . "time_slots" . ($contentType ? ":{$contentType}" : '') . ":{$days}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($contentType, $days) {
            $hourlyData = $this->viewLogRepository->getViewsByHour($contentType, $days);
            
            $result = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $found = $hourlyData->firstWhere('hour', $hour);
                $views = $found ? (int) $found->views : 0;
                
                $result[] = [
                    'hour' => $hour,
                    'views' => $views,
                    'time_label' => sprintf('%02d:00', $hour),
                    'period' => $this->getTimePeriod($hour)
                ];
            }
            
            return $result;
        });
    }

    /**
     * 取得時段標籤
     */
    private function getTimePeriod(int $hour): string
    {
        return match (true) {
            $hour >= 6 && $hour < 12 => '上午',
            $hour >= 12 && $hour < 18 => '下午',
            $hour >= 18 && $hour < 24 => '晚間',
            default => '深夜'
        };
    }

    /**
     * 取得綜合分析報告
     */
    public function getAnalyticsReport(string $contentType, string $period = 'week'): array
    {
        $cacheKey = $this->cachePrefix . "report:{$contentType}:{$period}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($contentType, $period) {
            $dateRange = $this->getDateRange($period);
            
            // 基礎統計
            $summary = $this->viewStatisticRepository->getViewsSummary($contentType);
            
            // 人口統計
            $ageDistribution = $this->getAgeDistribution($contentType, $period);
            $genderDistribution = $this->getGenderDistribution($contentType, $period);
            $memberGuestRatio = $this->getMemberGuestRatio($contentType, $period);
            
            // 成長統計
            $growthStats = $this->viewStatisticRepository->getGrowthStats();
            
            // 熱門內容
            $popularContent = $this->viewLogRepository->getPopularContent($contentType, 10, 
                $this->getDaysFromPeriod($period));
            
            return [
                'period' => $period,
                'date_range' => $dateRange,
                'summary' => $summary,
                'demographics' => [
                    'age_distribution' => $ageDistribution,
                    'gender_distribution' => $genderDistribution,
                    'member_guest_ratio' => $memberGuestRatio
                ],
                'growth' => $growthStats,
                'popular_content' => $popularContent->take(5)->values(),
                'generated_at' => now()->format('Y-m-d H:i:s')
            ];
        });
    }

    /**
     * 取得用戶觀看分析
     */
    public function getUserAnalytics(int $userId): array
    {
        $cacheKey = $this->cachePrefix . "user:{$userId}";
        
        return Cache::remember($cacheKey, 1800, function() use ($userId) { // 30分鐘快取
            $history = $this->viewLogRepository->getUserViewHistory($userId, 100);
            
            if ($history->isEmpty()) {
                return [
                    'total_views' => 0,
                    'content_types' => [],
                    'viewing_pattern' => [],
                    'favorite_genres' => [],
                    'recent_activity' => []
                ];
            }

            // 內容類型分析
            $contentTypes = $history->groupBy('content_type')->map(function ($items, $type) {
                return [
                    'type' => $type,
                    'count' => $items->count(),
                    'percentage' => 0 // 會在後面計算
                ];
            });
            
            $totalViews = $contentTypes->sum('count');
            $contentTypes = $contentTypes->map(function ($item) use ($totalViews) {
                $item['percentage'] = $totalViews > 0 ? round(($item['count'] / $totalViews) * 100, 2) : 0;
                return $item;
            });

            // 觀看模式（按小時）
            $hourlyPattern = $history->groupBy(function ($item) {
                return $item->created_at->format('H');
            })->map->count()->sortKeys();

            $viewingPattern = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $viewingPattern[] = [
                    'hour' => $hour,
                    'count' => $hourlyPattern[$hour] ?? 0,
                    'time_label' => sprintf('%02d:00', $hour)
                ];
            }

            return [
                'total_views' => $totalViews,
                'content_types' => $contentTypes->values(),
                'viewing_pattern' => $viewingPattern,
                'recent_activity' => $history->take(10)->map(function ($item) {
                    return [
                        'content_type' => $item->content_type,
                        'content_id' => $item->content_id,
                        'episode_id' => $item->episode_id,
                        'viewed_at' => $item->created_at->format('Y-m-d H:i:s')
                    ];
                })
            ];
        });
    }

    /**
     * 取得比較分析
     */
    public function getComparisonAnalysis(array $contentItems, string $period = 'week'): array
    {
        $result = [];
        $dateRange = $this->getDateRange($period);
        
        foreach ($contentItems as $item) {
            $contentType = $item['content_type'];
            $contentId = $item['content_id'];
            $episodeId = $item['episode_id'] ?? null;
            
            // 基礎數據
            $totalViews = app(ViewService::class)->getViewCount($contentType, $contentId, $episodeId);
            $uniqueViews = app(ViewService::class)->getUniqueViewCount($contentType, $contentId, $episodeId);
            $todayViews = app(ViewService::class)->getTodayViewCount($contentType, $contentId, $episodeId);
            
            // 人口統計
            $demographic = $this->viewDemographicRepository->getContentDemographic(
                $contentType, $contentId, $dateRange['start'], $dateRange['end']
            );
            
            $result[] = [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'episode_id' => $episodeId,
                'metrics' => [
                    'total_views' => $totalViews,
                    'unique_views' => $uniqueViews,
                    'today_views' => $todayViews,
                    'unique_rate' => $totalViews > 0 ? round(($uniqueViews / $totalViews) * 100, 2) : 0
                ],
                'demographics' => $demographic
            ];
        }
        
        return $result;
    }

    /**
     * 清除分析快取
     */
    public function clearAnalyticsCache(string $pattern = null): void
    {
        if ($pattern) {
            $keys = Cache::getRedis()->keys($this->cachePrefix . $pattern . '*');
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } else {
            $keys = Cache::getRedis()->keys($this->cachePrefix . '*');
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }

    /**
     * 根據期間取得日期範圍
     */
    private function getDateRange(string $period): array
    {
        $end = now()->format('Y-m-d');
        
        $start = match ($period) {
            'today' => now()->format('Y-m-d'),
            'yesterday' => now()->subDay()->format('Y-m-d'),
            'week' => now()->subDays(6)->format('Y-m-d'),
            'month' => now()->subDays(29)->format('Y-m-d'),
            'quarter' => now()->subDays(89)->format('Y-m-d'),
            'year' => now()->subDays(364)->format('Y-m-d'),
            default => now()->subDays(6)->format('Y-m-d')
        };
        
        return ['start' => $start, 'end' => $end];
    }

    /**
     * 從期間名稱取得天數
     */
    private function getDaysFromPeriod(string $period): int
    {
        return match ($period) {
            'today' => 1,
            'yesterday' => 1,
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            'year' => 365,
            default => 7
        };
    }

    /**
     * 取得實時統計
     */
    public function getRealTimeStats(): array
    {
        // 不使用快取，取得實時數據
        $now = now();
        
        return [
            'current_time' => $now->format('Y-m-d H:i:s'),
            'today_total' => $this->viewStatisticRepository->getViewsSummary()['total_daily_views'] ?? 0,
            'this_hour' => $this->viewLogRepository->getViewsByDateRange(
                'all', 0, $now->startOfHour(), $now
            ) ?? 0,
            'active_content_types' => $this->viewLogRepository->getViewStatsByContentType(1),
            'growth_rate' => $this->viewStatisticRepository->getGrowthStats()['daily_growth_rate'] ?? 0
        ];
    }
}