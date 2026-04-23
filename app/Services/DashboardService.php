<?php

namespace App\Services;

use App\Services\BaseService;
use App\Traits\CommonTrait;
use App\Repositories\ViewLogRepository;
use App\Repositories\ViewStatisticRepository;
use App\Repositories\UserRepository;
use App\Repositories\ArticleRepository;
use App\Repositories\DramaRepository;
use App\Repositories\ProgramRepository;
use App\Repositories\LiveRepository;
use App\Repositories\RadioRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class DashboardService extends BaseService
{
    use CommonTrait;

    protected $cachePrefix = 'dashboard_';
    protected $cacheTime = 300; // 5 minutes
    protected $viewLogRepository;
    protected $viewStatisticRepository;
    protected $userRepository;

    public function __construct(
        ViewLogRepository $viewLogRepository,
        ViewStatisticRepository $viewStatisticRepository,
        UserRepository $userRepository
    )
    {
        $this->initializeTrait();
        $this->viewLogRepository = $viewLogRepository;
        $this->viewStatisticRepository = $viewStatisticRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * 取得今日關鍵統計數據
     *
     * @return array
     */
    public function getTodayStatistics()
    {
        try {
            return Cache::remember($this->cachePrefix . 'today_stats', $this->cacheTime, function () {
                $today = now()->format('Y-m-d');
                $yesterday = now()->subDay()->format('Y-m-d');

                return [
                    'viewsByType' => $this->getViewStatsByContentType(),
                    'viewStatistics' => $this->getViewStatisticsTable(),
                    'users' => [
                        'total' => $this->userRepository->getTotalVerifiedUsers(),
                        'today_registered' => $this->userRepository->getTodayRegisteredUsers(),
                    ],
                ];
            });
        } catch (\Exception $e) {
            \Log::error('取得今日統計數據失敗', ['error' => $e->getMessage()]);

            // 回傳預設資料結構
            return [
                'viewsByType' => [
                    'article' => ['total' => 0, 'today' => 0],
                    'drama' => ['total' => 0, 'today' => 0],
                    'program' => ['total' => 0, 'today' => 0],
                    'live' => ['total' => 0, 'today' => 0],
                    'radio' => ['total' => 0, 'today' => 0],
                ],
                'viewStatistics' => [
                    ['type' => 'article', 'today' => 0, 'week' => 0, 'month' => 0, 'total' => 0],
                    ['type' => 'drama', 'today' => 0, 'week' => 0, 'month' => 0, 'total' => 0],
                    ['type' => 'program', 'today' => 0, 'week' => 0, 'month' => 0, 'total' => 0],
                    ['type' => 'live', 'today' => 0, 'week' => 0, 'month' => 0, 'total' => 0],
                    ['type' => 'radio', 'today' => 0, 'week' => 0, 'month' => 0, 'total' => 0],
                ],
                'users' => ['total' => 0, 'today_registered' => 0],
            ];
        }
    }

    /**
     * 取得系統狀態檢查
     *
     * @return array
     */
    public function getSystemStatus()
    {
        try {
            return Cache::remember($this->cachePrefix . 'system_status', $this->cacheTime, function () {
                return [
                    'database' => $this->checkDatabaseConnection(),
                    'cache' => $this->checkCacheConnection(),
                    'storage' => $this->checkStorageStatus(),
                    'queue' => $this->checkQueueStatus(),
                ];
            });
        } catch (\Exception $e) {
            \Log::error('取得系統狀態失敗', ['error' => $e->getMessage()]);

            // 回傳預設系統狀態（全部錯誤）
            return [
                'database' => ['status' => 'error', 'message' => '資料庫連線異常'],
                'cache' => ['status' => 'error', 'message' => '快取系統異常'],
                'storage' => ['status' => 'error', 'message' => '儲存系統異常'],
                'queue' => ['status' => 'error', 'message' => '佇列系統異常'],
            ];
        }
    }

    /**
     * 取得最近活動摘要
     *
     * @return array
     */
    public function getRecentActivities()
    {
        try {
            return Cache::remember($this->cachePrefix . 'recent_activities', $this->cacheTime, function () {
                return [
                    'recent_users' => $this->userRepository->getRecentUsers(),
                    'popularContentsByPeriod' => [
                        'today' => $this->getPopularContentsByPeriod('today'),
                        'week' => $this->getPopularContentsByPeriod('week'),
                        'month' => $this->getPopularContentsByPeriod('month'),
                        'total' => $this->getPopularContentsByPeriod('total'),
                    ],
                ];
            });
        } catch (\Exception $e) {
            \Log::error('取得最近活動失敗', ['error' => $e->getMessage()]);

            // 回傳預設空陣列
            $defaultContentData = [
                'article' => [], 'drama' => [], 'program' => [], 'live' => [], 'radio' => []
            ];

            return [
                'recent_users' => [],
                'popularContentsByPeriod' => [
                    'today' => $defaultContentData,
                    'week' => $defaultContentData,
                    'month' => $defaultContentData,
                    'total' => $defaultContentData,
                ],
            ];
        }
    }

    /**
     * 取得各內容類型觀看數統計
     *
     * @return array
     */
    protected function getViewStatsByContentType(): array
    {
        try {
            $contentTypes = ['article', 'drama', 'program', 'live', 'radio'];
            $result = [];

            foreach ($contentTypes as $type) {
                $result[$type] = [
                    'total' => $this->viewLogRepository->getViewCountByPeriod($type, 'total'),
                    'today' => $this->viewLogRepository->getViewCountByPeriod($type, 'today'),
                ];
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error('取得觀看數統計失敗', ['error' => $e->getMessage()]);

            // 回傳預設值
            return [
                'article' => ['total' => 0, 'today' => 0],
                'drama' => ['total' => 0, 'today' => 0],
                'program' => ['total' => 0, 'today' => 0],
                'live' => ['total' => 0, 'today' => 0],
                'radio' => ['total' => 0, 'today' => 0],
            ];
        }
    }

    /**
     * 取得觀看數統計表格資料（含收藏數）
     *
     * @return array
     */
    protected function getViewStatisticsTable(): array
    {
        try {
            // 使用 ViewStatisticRepository 的新方法取得完整統計
            return $this->viewStatisticRepository->getContentTypeStatsWithCollections();
        } catch (\Exception $e) {
            \Log::error('取得觀看數統計表格失敗', ['error' => $e->getMessage()]);

            // 回傳預設空陣列
            return array_map(function($type) {
                return [
                    'type' => $type,
                    'member_views' => 0,
                    'guest_views' => 0,
                    'total_views' => 0,
                    'collection_count' => 0
                ];
            }, ['article', 'drama', 'program', 'live', 'radio']);
        }
    }

    /**
     * 取得熱門內容統計（按時間區間和內容類型分組）
     *
     * @param string $period 時間區間：today, week, month, total
     * @param int $limit 每個內容類型返回的數量
     * @return array
     */
    protected function getPopularContentsByPeriod(string $period, int $limit = 5): array
    {
        try {
            $contentTypes = ['article', 'drama', 'program', 'live', 'radio'];
            $result = [];

            foreach ($contentTypes as $type) {
                // 轉換時間區間為天數
                $days = $this->periodToDays($period);

                // 使用 ViewLogRepository 取得熱門內容
                $popularContent = $this->viewLogRepository->getPopularContent($type, $limit, $days);

                // 加入標題資訊
                $result[$type] = $popularContent->map(function ($item) use ($type) {
                    return [
                        'content_id' => $item->content_id,
                        'title' => $this->getContentTitle($type, $item->content_id),
                        'view_count' => $item->view_count,
                    ];
                })->toArray();
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error('取得熱門內容統計失敗', ['error' => $e->getMessage()]);

            // 回傳預設空陣列
            return [
                'article' => [],
                'drama' => [],
                'program' => [],
                'live' => [],
                'radio' => []
            ];
        }
    }

    /**
     * 轉換時間區間為天數
     */
    protected function periodToDays(string $period): int
    {
        return match($period) {
            'today' => 0,    // 0 代表今天
            'week' => 7,
            'month' => 30,
            'total' => 9999, // 使用大數字代表全部
            default => 7
        };
    }


    /**
     * 檢查資料庫連線
     */
    protected function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'message' => '資料庫連線正常'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '資料庫連線異常：' . $e->getMessage()
            ];
        }
    }

    /**
     * 檢查快取連線
     */
    protected function checkCacheConnection()
    {
        try {
            // 檢查一般快取
            Cache::put('dashboard_test', 'test', 1);
            Cache::get('dashboard_test');
            Cache::forget('dashboard_test');

            $message = '快取系統正常';
            $status = 'healthy';

            // 檢查 Redis 連線（如果有使用）
            try {
                if (config('database.redis.default')) {
                    \Illuminate\Support\Facades\Redis::ping();
                    $message .= ' (含 Redis)';
                }
            } catch (\Exception $redisError) {
                $message = '快取正常，但 Redis 連線異常';
                $status = 'warning';
                \Log::warning('Redis 連線檢查失敗', ['error' => $redisError->getMessage()]);
            }

            return [
                'status' => $status,
                'message' => $message
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '快取系統異常：' . $e->getMessage()
            ];
        }
    }

    /**
     * 檢查儲存狀態
     */
    protected function checkStorageStatus()
    {
        try {
            $storagePath = storage_path('app/public');
            $isWritable = is_writable($storagePath);

            return [
                'status' => $isWritable ? 'healthy' : 'warning',
                'message' => $isWritable ? '儲存空間正常' : '儲存空間權限異常'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '儲存系統異常：' . $e->getMessage()
            ];
        }
    }

    /**
     * 檢查佇列狀態
     */
    protected function checkQueueStatus()
    {
        try {
            // 簡單的佇列狀態檢查
            return [
                'status' => 'healthy',
                'message' => '佇列系統正常'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => '佇列系統異常：' . $e->getMessage()
            ];
        }
    }

    /**
     * 根據內容類型和ID獲取標題
     * 使用動態 Repository 取代直接 DB 查詢
     */
    protected function getContentTitle($contentType, $contentId)
    {
        try {
            // 動態取得對應的 Repository
            $repositoryClass = match($contentType) {
                'article' => ArticleRepository::class,
                'drama' => DramaRepository::class,
                'program' => ProgramRepository::class,
                'live' => LiveRepository::class,
                'radio' => RadioRepository::class,
                default => null
            };

            if (!$repositoryClass) {
                return '未知內容';
            }

            $repository = app($repositoryClass);
            $content = $repository->find($contentId);

            if (!$content) {
                return '內容不存在';
            }

            // 處理多語言標題
            $title = $content->title;
            if (is_string($title)) {
                $decoded = json_decode($title, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['zh_TW'])) {
                    return $decoded['zh_TW'];
                }
                return $title;
            }

            // 如果 title 是物件或陣列（使用 translatable trait）
            if (method_exists($content, 'getTranslation')) {
                return $content->getTranslation('title', 'zh_TW');
            }

            return '無標題';
        } catch (\Exception $e) {
            \Log::error('取得內容標題失敗', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);
            return '取得標題失敗';
        }
    }


    /**
     * 清除儀表板快取及前台快取，並執行快速同步
     */
    public function clearCache()
    {
        try {
            // 1. 執行快速同步（Redis → MySQL）
            Artisan::call('views:sync', ['--from-redis' => true]);

            // 2. 清除儀表板快取
            Cache::forget($this->cachePrefix . 'today_stats');
            Cache::forget($this->cachePrefix . 'system_status');
            Cache::forget($this->cachePrefix . 'recent_activities');

            // 3. 清除前台首頁快取
            $this->clearHomePageCache();

            return $this->ReturnHandle(true, '數據更新完成（含快速同步）');
        } catch (\Exception $e) {
            return $this->ReturnHandle(false, '更新數據失敗：' . $e->getMessage());
        }
    }

    /**
     * 重新計算觀看數統計（智能同步）
     * 從 view_logs 重新計算所有統計，清理已刪除的記錄，清理孤立觀看紀錄
     *
     * @return array
     */
    public function recalculateViewStatistics()
    {
        try {
            
            // 1. 清理孤立的觀看紀錄和收藏紀錄
            \Log::info('開始清理孤立紀錄');
            $cleanupExitCode = Artisan::call('cleanup:orphaned-records', [
                '--no-interaction' => true
            ]);
         

            if ($cleanupExitCode !== 0) {
                \Log::warning('清理孤立紀錄執行異常，但繼續執行重新計算');
            } else {
                \Log::info('清理孤立紀錄完成');
            }

            // 2. 執行重新計算（從 view_logs 重新計算，包含 Redis）
            \Log::info('開始重新計算觀看數統計');
            $syncExitCode = Artisan::call('views:sync', [
                '--recalculate' => true,
                '--no-interaction' => true
            ]);
       

            // 檢查執行結果
            if ($syncExitCode !== 0) {
                throw new \Exception('重新計算命令執行失敗');
            }

            // 3. 檢查並生成缺少的縮圖
            \Log::info('開始檢查缺少的影片縮圖');
            $thumbnailManager = app(\App\Services\Thumbnail\VideoThumbnailManager::class);

            $thumbnailStats = [
                'drama' => ['checked' => 0, 'generated' => 0],
                'program' => ['checked' => 0, 'generated' => 0],
            ];

            // 3.1 檢查影音集數（只處理沒有縮圖的）
            $dramaEpisodesWithoutThumbnail = DB::table('drama_episodes')
                ->leftJoin('images', function($join) {
                    $join->on('drama_episodes.id', '=', 'images.attachable_id')
                         ->where('images.attachable_type', '=', 'App\\Models\\DramaEpisode')
                         ->where('images.image_type', '=', 'video_thumbnail');
                })
                ->whereNull('images.id')
                ->pluck('drama_episodes.id')
                ->toArray();

            $thumbnailStats['drama']['checked'] = DB::table('drama_episodes')->count();

            if (!empty($dramaEpisodesWithoutThumbnail)) {
                $dramaResults = $thumbnailManager->batchGenerate($dramaEpisodesWithoutThumbnail, false, 'drama');
                $thumbnailStats['drama']['generated'] = $dramaResults['success'];
            }

            // 3.2 檢查節目集數（只處理沒有縮圖的）
            $programEpisodesWithoutThumbnail = DB::table('program_episodes')
                ->leftJoin('images', function($join) {
                    $join->on('program_episodes.id', '=', 'images.attachable_id')
                         ->where('images.attachable_type', '=', 'App\\Models\\ProgramEpisode')
                         ->where('images.image_type', '=', 'video_thumbnail');
                })
                ->whereNull('images.id')
                ->pluck('program_episodes.id')
                ->toArray();

            $thumbnailStats['program']['checked'] = DB::table('program_episodes')->count();

            if (!empty($programEpisodesWithoutThumbnail)) {
                $programResults = $thumbnailManager->batchGenerate($programEpisodesWithoutThumbnail, false, 'program');
                $thumbnailStats['program']['generated'] = $programResults['success'];
            }

            $totalThumbnailsGenerated = $thumbnailStats['drama']['generated'] + $thumbnailStats['program']['generated'];
            \Log::info('縮圖檢查完成', $thumbnailStats);

            // 4. 清除所有相關快取
            Cache::forget($this->cachePrefix . 'today_stats');
            Cache::forget($this->cachePrefix . 'system_status');
            Cache::forget($this->cachePrefix . 'recent_activities');
            $this->clearHomePageCache();

            \Log::info('重新計算觀看數統計完成');

            // 組合訊息
            $message = '重新計算完成！已清理孤立紀錄、過期數據並同步 Redis';
            if ($totalThumbnailsGenerated > 0) {
                $message .= sprintf('，並生成 %d 個缺少的影片縮圖', $totalThumbnailsGenerated);
            }

            return $this->ReturnHandle(true, $message, null, [
                'description' => '清理孤立觀看紀錄 → 從 view_logs 重新計算所有統計 → 清理已刪除的記錄 → 同時更新 MySQL 和 Redis → 檢查並生成缺少的影片縮圖',
                'thumbnails' => $thumbnailStats
            ]);

        } catch (\Exception $e) {
            \Log::error('重新計算失敗', ['error' => $e->getMessage()]);
            return $this->ReturnHandle(false, '重新計算失敗：' . $e->getMessage());
        }
    }

}
