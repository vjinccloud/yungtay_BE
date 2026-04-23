<?php

namespace App\Services;

use App\Repositories\ViewRankingRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ViewRankingService extends BaseService
{
    protected $viewRankingRepository;
    
    // 快取設定
    protected $cachePrefix = 'sjtv:rankings:';
    protected $cacheExpire = 1800; // 30 分鐘

    public function __construct(ViewRankingRepository $viewRankingRepository)
    {
        parent::__construct($viewRankingRepository);
        $this->viewRankingRepository = $viewRankingRepository;
    }

    /**
     * 取得排行榜
     */
    public function getTopContent(string $contentType, string $period, int $limit = 10, string $date = null): array
    {
        $cacheKey = $this->cachePrefix . "top:{$contentType}:{$period}:{$limit}" . ($date ? ":{$date}" : '');
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($contentType, $period, $limit, $date) {
            $rankings = $this->viewRankingRepository->getRankings($period, $date, $contentType, $limit);
            
            return [
                'period_type' => $period,
                'period_date' => $date ?: $this->getCurrentPeriodDate($period),
                'content_type' => $contentType,
                'rankings' => $rankings->map(function ($item, $index) {
                    return [
                        'ranking' => $item->ranking,
                        'content_type' => $item->content_type,
                        'content_id' => $item->content_id,
                        'content_title' => $item->content_title,
                        'view_count' => $item->view_count,
                        'unique_count' => $item->unique_count,
                        'growth_rate' => $item->growth_rate,
                        'formatted_views' => $item->formatted_views,
                        'formatted_growth_rate' => $item->formatted_growth_rate,
                        'ranking_badge_class' => $item->ranking_badge_class,
                        'is_top_three' => $item->is_top_three,
                        'growth_trend' => $item->growth_trend
                    ];
                })->toArray()
            ];
        });
    }

    /**
     * 取得跨類型綜合排行榜
     */
    public function getCrossTypeRankings(string $period, int $limit = 20, string $date = null): array
    {
        $cacheKey = $this->cachePrefix . "cross:{$period}:{$limit}" . ($date ? ":{$date}" : '');
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($period, $limit, $date) {
            $rankings = $this->viewRankingRepository->getCrossTypeRankings($period, $date, $limit);
            
            return [
                'period_type' => $period,
                'period_date' => $date ?: $this->getCurrentPeriodDate($period),
                'rankings' => $rankings->map(function ($item) {
                    return [
                        'cross_ranking' => $item->cross_ranking,
                        'original_ranking' => $item->ranking,
                        'content_type' => $item->content_type,
                        'content_id' => $item->content_id,
                        'content_title' => $item->content_title,
                        'view_count' => $item->view_count,
                        'unique_count' => $item->unique_count,
                        'growth_rate' => $item->growth_rate,
                        'formatted_views' => $item->formatted_views,
                        'growth_trend' => $item->growth_trend
                    ];
                })->toArray()
            ];
        });
    }

    /**
     * 取得按分類的排行榜
     */
    public function getRankingByCategory(): array
    {
        $cacheKey = $this->cachePrefix . 'by_category';
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() {
            $contentTypes = ['drama', 'program', 'article', 'live', 'radio'];
            $periods = ['daily', 'weekly', 'monthly'];
            $result = [];
            
            foreach ($periods as $period) {
                $result[$period] = [];
                foreach ($contentTypes as $contentType) {
                    $rankings = $this->getTopContent($contentType, $period, 5);
                    $result[$period][$contentType] = $rankings['rankings'];
                }
            }
            
            return $result;
        });
    }

    /**
     * 取得上升最快排行榜
     */
    public function getFastestRising(string $period, string $contentType = null, int $limit = 10): array
    {
        $cacheKey = $this->cachePrefix . "rising:{$period}" . ($contentType ? ":{$contentType}" : '') . ":{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($period, $contentType, $limit) {
            $rising = $this->viewRankingRepository->getFastestRising($period, null, $contentType, $limit);
            
            return [
                'period_type' => $period,
                'content_type' => $contentType,
                'rising_content' => $rising->map(function ($item, $index) {
                    return [
                        'rank' => $index + 1,
                        'original_ranking' => $item->ranking,
                        'content_type' => $item->content_type,
                        'content_id' => $item->content_id,
                        'content_title' => $item->content_title,
                        'view_count' => $item->view_count,
                        'growth_rate' => $item->growth_rate,
                        'formatted_growth_rate' => $item->formatted_growth_rate,
                        'growth_trend' => $item->growth_trend
                    ];
                })->toArray()
            ];
        });
    }

    /**
     * 取得新進榜內容
     */
    public function getNewEntries(string $period, string $contentType = null, int $limit = 10): array
    {
        $cacheKey = $this->cachePrefix . "new_entries:{$period}" . ($contentType ? ":{$contentType}" : '') . ":{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($period, $contentType, $limit) {
            $newEntries = $this->viewRankingRepository->getNewEntries($period, null, $contentType, $limit);
            
            return [
                'period_type' => $period,
                'content_type' => $contentType,
                'new_entries' => $newEntries->map(function ($item) {
                    return [
                        'ranking' => $item->ranking,
                        'content_type' => $item->content_type,
                        'content_id' => $item->content_id,
                        'content_title' => $item->content_title,
                        'view_count' => $item->view_count,
                        'formatted_views' => $item->formatted_views,
                        'is_new' => true
                    ];
                })->toArray()
            ];
        });
    }

    /**
     * 取得內容的排名歷史
     */
    public function getContentRankingHistory(string $contentType, int $contentId, string $period = 'weekly', int $periods = 10): array
    {
        $cacheKey = $this->cachePrefix . "history:{$contentType}:{$contentId}:{$period}:{$periods}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($contentType, $contentId, $period, $periods) {
            $history = $this->viewRankingRepository->getContentRankingHistory($contentType, $contentId, $period, $periods);
            
            return [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'period_type' => $period,
                'history' => $history->map(function ($item) {
                    return [
                        'period_date' => $item->period_date->format('Y-m-d'),
                        'ranking' => $item->ranking,
                        'view_count' => $item->view_count,
                        'growth_rate' => $item->growth_rate,
                        'formatted_date' => $item->period_date->format('m/d'),
                        'is_current' => $item->isCurrent()
                    ];
                })->toArray()
            ];
        });
    }

    /**
     * 取得排行榜摘要
     */
    public function getRankingSummary(string $period): array
    {
        $cacheKey = $this->cachePrefix . "summary:{$period}";
        
        return Cache::remember($cacheKey, $this->cacheExpire, function() use ($period) {
            return $this->viewRankingRepository->getRankingSummary($period);
        });
    }

    /**
     * 更新排行榜
     */
    public function updateRankings(array $periods = null, array $contentTypes = null): array
    {
        try {
            $periods = $periods ?: ['daily', 'weekly', 'monthly'];
            $contentTypes = $contentTypes ?: ['drama', 'program', 'article', 'live', 'radio'];
            
            $results = [];
            $totalUpdated = 0;
            
            foreach ($periods as $period) {
                $periodDate = $this->getCurrentPeriodDate($period);
                $results[$period] = [];
                
                foreach ($contentTypes as $contentType) {
                    try {
                        $count = $this->viewRankingRepository->generateRankingsFromStats(
                            $period,
                            $periodDate,
                            $contentType,
                            100 // 取前100名
                        );
                        
                        $results[$period][$contentType] = $count;
                        $totalUpdated += $count;
                        
                        // 清除相關快取
                        $this->clearRankingCache($contentType, $period);
                        
                    } catch (\Exception $e) {
                        \Log::error("Failed to update {$period} rankings for {$contentType}", [
                            'error' => $e->getMessage()
                        ]);
                        $results[$period][$contentType] = 0;
                    }
                }
            }
            
            return $this->ReturnHandle(true, '排行榜更新完成', null, [
                'total_updated' => $totalUpdated,
                'details' => $results
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Update rankings failed', ['error' => $e->getMessage()]);
            return $this->ReturnHandle(false, '排行榜更新失敗：' . $e->getMessage());
        }
    }

    /**
     * 取得當前期間日期
     */
    private function getCurrentPeriodDate(string $period): string
    {
        return match($period) {
            'daily' => now()->format('Y-m-d'),
            'weekly' => now()->startOfWeek()->format('Y-m-d'),
            'monthly' => now()->startOfMonth()->format('Y-m-d'),
            'yearly' => now()->startOfYear()->format('Y-m-d'),
            default => now()->format('Y-m-d')
        };
    }

    /**
     * 清除排行榜快取
     */
    public function clearRankingCache(string $contentType = null, string $period = null): void
    {
        $patterns = [];
        
        if ($contentType && $period) {
            $patterns[] = "top:{$contentType}:{$period}:*";
            $patterns[] = "rising:{$period}:{$contentType}:*";
            $patterns[] = "new_entries:{$period}:{$contentType}:*";
        } elseif ($contentType) {
            $patterns[] = "top:{$contentType}:*";
            $patterns[] = "history:{$contentType}:*";
        } elseif ($period) {
            $patterns[] = "*:{$period}:*";
            $patterns[] = "cross:{$period}:*";
            $patterns[] = "summary:{$period}";
        } else {
            $patterns[] = '*';
        }
        
        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($this->cachePrefix . $pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
        
        // 清除分類快取
        Cache::forget($this->cachePrefix . 'by_category');
    }

    /**
     * 取得排行榜變化
     */
    public function getRankingChanges(string $contentType, string $period): array
    {
        $currentDate = $this->getCurrentPeriodDate($period);
        $previousDate = $this->getPreviousPeriodDate($period, $currentDate);
        
        $current = $this->viewRankingRepository->getRankings($period, $currentDate, $contentType, 50);
        $previous = $this->viewRankingRepository->getRankings($period, $previousDate, $contentType, 50);
        
        $changes = [];
        
        foreach ($current as $currentItem) {
            $previousItem = $previous->firstWhere('content_id', $currentItem->content_id);
            
            $change = [
                'content_type' => $currentItem->content_type,
                'content_id' => $currentItem->content_id,
                'content_title' => $currentItem->content_title,
                'current_ranking' => $currentItem->ranking,
                'previous_ranking' => $previousItem ? $previousItem->ranking : null,
                'ranking_change' => 0,
                'change_type' => 'stable',
                'view_count' => $currentItem->view_count,
                'growth_rate' => $currentItem->growth_rate
            ];
            
            if ($previousItem) {
                $change['ranking_change'] = $previousItem->ranking - $currentItem->ranking; // 正數表示上升
                
                if ($change['ranking_change'] > 0) {
                    $change['change_type'] = 'up';
                } elseif ($change['ranking_change'] < 0) {
                    $change['change_type'] = 'down';
                }
            } else {
                $change['change_type'] = 'new';
            }
            
            $changes[] = $change;
        }
        
        return $changes;
    }

    /**
     * 取得前一期間日期
     */
    private function getPreviousPeriodDate(string $period, string $currentDate): string
    {
        $date = Carbon::parse($currentDate);
        
        return match($period) {
            'daily' => $date->subDay()->format('Y-m-d'),
            'weekly' => $date->subWeek()->format('Y-m-d'),
            'monthly' => $date->subMonth()->format('Y-m-d'),
            'yearly' => $date->subYear()->format('Y-m-d'),
            default => $date->subDay()->format('Y-m-d')
        };
    }

    /**
     * 清理舊排行榜
     */
    public function cleanOldRankings(int $daysToKeep = 365): array
    {
        try {
            $deletedCount = $this->viewRankingRepository->cleanOldRankings($daysToKeep);
            
            return $this->ReturnHandle(true, '舊排行榜清理完成', null, [
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Clean old rankings failed', ['error' => $e->getMessage()]);
            return $this->ReturnHandle(false, '清理失敗：' . $e->getMessage());
        }
    }

    /**
     * 驗證排行榜數據完整性
     */
    public function validateRankings(string $period, string $date = null): array
    {
        $date = $date ?: $this->getCurrentPeriodDate($period);
        $contentTypes = ['drama', 'program', 'article', 'live', 'radio'];
        
        $validation = [
            'period_type' => $period,
            'period_date' => $date,
            'issues' => [],
            'summary' => []
        ];
        
        foreach ($contentTypes as $contentType) {
            $rankings = $this->viewRankingRepository->getRankings($period, $date, $contentType, 100);
            
            // 檢查排名連續性
            $expectedRanking = 1;
            foreach ($rankings as $item) {
                if ($item->ranking !== $expectedRanking) {
                    $validation['issues'][] = [
                        'type' => 'ranking_gap',
                        'content_type' => $contentType,
                        'expected' => $expectedRanking,
                        'actual' => $item->ranking
                    ];
                }
                $expectedRanking++;
            }
            
            $validation['summary'][$contentType] = [
                'total_entries' => $rankings->count(),
                'max_ranking' => $rankings->max('ranking'),
                'min_views' => $rankings->min('view_count'),
                'max_views' => $rankings->max('view_count')
            ];
        }
        
        return $validation;
    }
}