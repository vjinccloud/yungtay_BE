<?php

namespace App\Services;

use App\Models\ViewLog;
use App\Models\User;
use App\Repositories\ViewLogRepository;
use App\Repositories\ViewStatisticRepository;
use App\Repositories\ViewDemographicRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class ViewService extends BaseService
{
    protected $viewLogRepository;
    protected $viewStatisticRepository;
    protected $viewDemographicRepository;
    
    // Redis 設定
    protected $redisPrefix = 'sjtv:views:';
    protected $redisExpire = 86400; // 24 小時
    protected $antiFloodWindow = 300; // 5 分鐘防刷間隔
    protected $maxViewsPerHour = 100; // 每小時最大觀看次數

    public function __construct(ViewLogRepository $viewLogRepository)
    {
        parent::__construct($viewLogRepository);
        $this->viewLogRepository = $viewLogRepository;
        $this->viewStatisticRepository = app(ViewStatisticRepository::class);
        $this->viewDemographicRepository = app(ViewDemographicRepository::class);
    }

    /**
     * 記錄觀看
     */
    public function recordView(string $contentType, int $contentId, ?int $episodeId = null, ?int $userId = null): array
    {
        try {
            // 取得用戶和IP資訊
            $user = $userId ? User::find($userId) : null;
            $ipAddress = request()->ip();
            $userAgent = request()->userAgent();

            // 防刷檢查
            if (!$this->passAntiFloodCheck($contentType, $contentId, $episodeId, $userId, $ipAddress)) {
                return $this->ReturnHandle(false, '操作過於頻繁，請稍後再試');
            }

            DB::beginTransaction();

            // 1. 記錄觀看日誌
            $viewLog = $this->viewLogRepository->recordView([
                'content_type' => $contentType,
                'content_id' => $contentId,
                'episode_id' => $episodeId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent
            ]);

            // 2. 檢查是否為唯一觀看
            $isUnique = $this->viewLogRepository->isUniqueView(
                $contentType, 
                $contentId, 
                $episodeId, 
                $userId, 
                $ipAddress,
                $this->antiFloodWindow
            );

            // 3. 更新統計數據（有錯誤處理）
            try {
                $this->updateStatistics($contentType, $contentId, $episodeId, $isUnique, $userId);
            } catch (\Exception $statsError) {
                \Log::warning('統計數據更新失敗，繼續執行其他步驟', [
                    'content_type' => $contentType,
                    'content_id' => $contentId,
                    'error' => $statsError->getMessage()
                ]);
            }

            // 4. 人口統計由定時任務處理（demographics:aggregate-daily）
            // 不需要即時更新，避免影響記錄效能

            // 5. 更新 Redis 計數器
            $this->updateRedisCounters($contentType, $contentId, $episodeId, $isUnique, $userId);

            DB::commit();

            return $this->ReturnHandle(true, '觀看記錄成功', null, [
                'view_log_id' => $viewLog->id,
                'is_unique' => $isUnique
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Record view failed', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'episode_id' => $episodeId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return $this->ReturnHandle(false, '記錄觀看失敗');
        }
    }

    /**
     * 防刷檢查
     */
    protected function passAntiFloodCheck(string $contentType, int $contentId, ?int $episodeId, ?int $userId, string $ipAddress): bool
    {
        try {
            // 檢查同一內容在短時間內的觀看次數
            $identifier = $userId ? "user:{$userId}" : "ip:{$ipAddress}";
            $key = $this->redisPrefix . "flood:{$identifier}:{$contentType}:{$contentId}";
            
            if ($episodeId) {
                $key .= ":{$episodeId}";
            }

            $count = Redis::get($key);
            if ($count && $count >= 3) { // 5分鐘內最多3次
                return false;
            }

            // 檢查每小時觀看總次數（防止機器人）
            $hourlyKey = $this->redisPrefix . "hourly:{$identifier}:" . date('YmdH');
            $hourlyCount = Redis::get($hourlyKey);
            if ($hourlyCount && $hourlyCount >= $this->maxViewsPerHour) {
                return false;
            }

            // 更新計數器
            Redis::incr($key);
            Redis::expire($key, $this->antiFloodWindow);
            
            Redis::incr($hourlyKey);
            Redis::expire($hourlyKey, 3600);

            return true;
        } catch (\Exception $e) {
            // Redis 錯誤時允許通過
            \Log::warning('Redis anti-flood check failed', ['error' => $e->getMessage()]);
            return true;
        }
    }

    /**
     * 更新統計數據
     */
    protected function updateStatistics(string $contentType, int $contentId, ?int $episodeId, bool $isUnique, ?int $userId = null): void
    {
        $statistic = $this->viewStatisticRepository->findOrCreateStatistic($contentType, $contentId, $episodeId);
        $isMember = !is_null($userId);
        $this->viewStatisticRepository->updateStatistic($statistic, $isUnique, 1, $isMember);
    }

    /**
     * 更新 Redis 計數器
     */
    protected function updateRedisCounters(string $contentType, int $contentId, ?int $episodeId, bool $isUnique, ?int $userId = null): void
    {
        try {
            $baseKey = $this->redisPrefix . "{$contentType}:{$contentId}";
            if ($episodeId) {
                $baseKey .= ":{$episodeId}";
            }

            // 總觀看數
            Redis::incr($baseKey . ':total');
            Redis::expire($baseKey . ':total', $this->redisExpire);

            // 會員 vs 訪客觀看數
            if ($userId) {
                Redis::incr($baseKey . ':member');
                Redis::expire($baseKey . ':member', $this->redisExpire);
            } else {
                Redis::incr($baseKey . ':guest');
                Redis::expire($baseKey . ':guest', $this->redisExpire);
            }

            // 今日觀看數
            $todayKey = $baseKey . ':daily:' . date('Ymd');
            Redis::incr($todayKey);
            Redis::expire($todayKey, 86400 + 3600); // 多保存1小時

            // 唯一觀看數（如果是唯一觀看）
            if ($isUnique) {
                Redis::incr($baseKey . ':unique');
                Redis::expire($baseKey . ':unique', $this->redisExpire);
            }

        } catch (\Exception $e) {
            \Log::warning('[ViewService] Redis counter update failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 取得觀看數（優先從 Redis）
     */
    public function getViewCount(string $contentType, int $contentId, ?int $episodeId = null): int
    {
        try {
            $baseKey = $this->redisPrefix . "{$contentType}:{$contentId}";
            if ($episodeId) {
                $baseKey .= ":{$episodeId}";
            }

            $count = Redis::get($baseKey . ':total');
            
            if ($count !== null) {
                return (int) $count;
            }

            // Redis 沒有數據，從資料庫取得並快取
            $count = $this->viewLogRepository->getTotalViews($contentType, $contentId, $episodeId);
            Redis::setex($baseKey . ':total', $this->redisExpire, $count);
            
            return $count;
        } catch (\Exception $e) {
            // Redis 錯誤時直接查詢資料庫
            return $this->viewLogRepository->getTotalViews($contentType, $contentId, $episodeId);
        }
    }

    /**
     * 取得唯一觀看數
     */
    public function getUniqueViewCount(string $contentType, int $contentId, ?int $episodeId = null): int
    {
        try {
            $baseKey = $this->redisPrefix . "{$contentType}:{$contentId}";
            if ($episodeId) {
                $baseKey .= ":{$episodeId}";
            }

            $count = Redis::get($baseKey . ':unique');
            
            if ($count !== null) {
                return (int) $count;
            }

            // 從資料庫取得並快取
            $count = $this->viewLogRepository->getUniqueViews($contentType, $contentId, $episodeId);
            Redis::setex($baseKey . ':unique', $this->redisExpire, $count);
            
            return $count;
        } catch (\Exception $e) {
            return $this->viewLogRepository->getUniqueViews($contentType, $contentId, $episodeId);
        }
    }

    /**
     * 取得今日觀看數
     */
    public function getTodayViewCount(string $contentType, int $contentId, ?int $episodeId = null): int
    {
        try {
            $baseKey = $this->redisPrefix . "{$contentType}:{$contentId}";
            if ($episodeId) {
                $baseKey .= ":{$episodeId}";
            }

            $todayKey = $baseKey . ':daily:' . date('Ymd');
            $count = Redis::get($todayKey);
            
            if ($count !== null) {
                return (int) $count;
            }

            // 從資料庫取得並快取
            $count = $this->viewLogRepository->getTodayViews($contentType, $contentId, $episodeId);
            Redis::setex($todayKey, 86400, $count);
            
            return $count;
        } catch (\Exception $e) {
            return $this->viewLogRepository->getTodayViews($contentType, $contentId, $episodeId);
        }
    }

    /**
     * 批量取得觀看數
     */
    public function getBatchViewCounts(array $items): array
    {
        $result = [];
        
        foreach ($items as $item) {
            $key = "{$item['content_type']}:{$item['content_id']}";
            if (isset($item['episode_id'])) {
                $key .= ":{$item['episode_id']}";
            }
            
            $result[$key] = [
                'total_views' => $this->getViewCount($item['content_type'], $item['content_id'], $item['episode_id'] ?? null),
                'unique_views' => $this->getUniqueViewCount($item['content_type'], $item['content_id'], $item['episode_id'] ?? null),
                'today_views' => $this->getTodayViewCount($item['content_type'], $item['content_id'], $item['episode_id'] ?? null)
            ];
        }
        
        return $result;
    }

    /**
     * 同步 Redis 數據到資料庫（定時任務用）
     */
    public function syncToDatabase(): void
    {
        try {
            \Log::info('Starting view data sync to database');

            // 重新計算所有統計數據
            $this->viewStatisticRepository->batchUpdateFromLogs();

            \Log::info('View data sync completed successfully');
        } catch (\Exception $e) {
            \Log::error('View data sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 清理舊數據
     */
    public function cleanOldData(int $logDays = 90, int $demographicDays = 365): array
    {
        try {
            $deletedLogs = $this->viewLogRepository->cleanOldRecords($logDays);
            $deletedDemographics = $this->viewDemographicRepository->cleanOldRecords($demographicDays);
            
            return $this->ReturnHandle(true, '清理完成', null, [
                'deleted_logs' => $deletedLogs,
                'deleted_demographics' => $deletedDemographics
            ]);
        } catch (\Exception $e) {
            \Log::error('Clean old data failed', ['error' => $e->getMessage()]);
            return $this->ReturnHandle(false, '清理失敗：' . $e->getMessage());
        }
    }

    /**
     * 取得觀看數摘要
     */
    public function getViewsSummary(string $contentType = null): array
    {
        return $this->viewStatisticRepository->getViewsSummary($contentType);
    }

    /**
     * 取得用戶觀看歷史（分頁版本）
     */
    public function getUserViewHistory($userId, $filters = [], $perPage = 16)
    {
        return $this->viewLogRepository->getUserViewHistory($userId, $filters, $perPage);
    }


    /**
     * 檢查觀看權限（預留接口）
     */
    protected function checkViewPermission(string $contentType, int $contentId, ?int $userId = null): bool
    {
        // 可在此實作權限檢查邏輯
        // 例如：VIP內容檢查、地區限制等
        return true;
    }

    /**
     * 取得熱門內容
     */
    public function getPopularContent(string $contentType, int $limit = 10, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $this->viewLogRepository->getPopularContent($contentType, $limit, $days);
    }

    /**
     * 重置日觀看數（定時任務用）
     */
    public function resetDailyViews(): array
    {
        try {
            $resetCount = $this->viewStatisticRepository->resetDailyViews();

            return $this->ReturnHandle(true, '日觀看數重置完成', null, [
                'reset_count' => $resetCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Reset daily views failed', ['error' => $e->getMessage()]);
            return $this->ReturnHandle(false, '重置失敗：' . $e->getMessage());
        }
    }

    /**
     * 取得內容的觀看統計數據（用於觀看紀錄頁面）
     */
    public function getContentViewStatistics(string $contentType, int $contentId): array
    {
        try {
            // 計算總觀看次數（聚合所有集數）
            $totalViews = $this->viewStatisticRepository->getTotalViewsByContent($contentType, $contentId);

            // 計算會員觀看次數
            $memberViews = $this->viewLogRepository->getMemberViewsByContent($contentType, $contentId);

            // 計算訪客觀看次數
            $guestViews = $this->viewLogRepository->getGuestViewsByContent($contentType, $contentId);

            // 計算收藏人數
            $collectionCount = DB::table('user_collections')
                ->where('content_type', $contentType)
                ->where('content_id', $contentId)
                ->count();

            return [
                'total_views' => $totalViews,
                'member_views' => $memberViews,
                'guest_views' => $guestViews,
                'collection_count' => $collectionCount,
            ];
        } catch (\Exception $e) {
            \Log::error('Get content view statistics failed', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return [
                'total_views' => 0,
                'member_views' => 0,
                'guest_views' => 0,
                'collection_count' => 0,
            ];
        }
    }

    /**
     * 取得集數觀看明細（用於 DataTable AJAX）
     */
    public function getEpisodeViewLogs(string $contentType, int $contentId, array $filters = []): array
    {
        try {
            $perPage = $filters['length'] ?? 25;
            $page = isset($filters['start']) ? floor($filters['start'] / $perPage) + 1 : 1;

            // 從 Repository 取得分頁資料（傳遞篩選條件）
            $result = $this->viewStatisticRepository->getEpisodeViewStats(
                $contentType,
                $contentId,
                $filters,
                $perPage,
                $page
            );

            return [
                'data' => $result['data'],
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['total'],
            ];
        } catch (\Exception $e) {
            \Log::error('Get episode view logs failed', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return [
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
            ];
        }
    }
}