<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Services\HomeService;

class SyncViewStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'views:sync 
        {--from-redis : 從 Redis 同步到資料庫} 
        {--to-redis : 從資料庫同步到 Redis}
        {--recalculate : 從 view_logs 重新計算所有統計}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步觀看統計數據 (Redis <-> MySQL)';

    protected $redisPrefix = 'sjtv:views:';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fromRedis = $this->option('from-redis');
        $toRedis = $this->option('to-redis');
        $recalculate = $this->option('recalculate');

        // 如果要重新計算，優先執行
        if ($recalculate) {
            $this->recalculateFromViewLogs();
            return Command::SUCCESS;
        }

        // 預設行為：從 Redis 同步到資料庫
        if (!$fromRedis && !$toRedis) {
            $fromRedis = true;
        }

        if ($fromRedis) {
            $this->syncFromRedisToDatabase();
        }
        
        if ($toRedis) {
            $this->syncFromDatabaseToRedis();
        }

        return Command::SUCCESS;
    }

    /**
     * 從 Redis 同步到資料庫
     */
    protected function syncFromRedisToDatabase()
    {
        $this->info('=== 從 Redis 同步到資料庫 ===');
        $this->newLine();

        try {
            // 1. 取得所有 Redis 中的觀看數據 keys
            $this->info('1. 掃描 Redis 中的觀看數據...');
            // 注意：Redis::keys 會自動加上 Laravel 的前綴，所以我們只需要搜尋我們的部分
            $keys = Redis::keys('*' . $this->redisPrefix . '*:total');
            $this->info("找到 " . count($keys) . " 個 Redis 記錄");

            if (count($keys) > 0) {
                $bar = $this->output->createProgressBar(count($keys));
                $bar->start();

                foreach ($keys as $key) {
                    // 移除所有 Laravel 前綴（可能重複出現）
                    $cleanKey = $key;
                    while (strpos($cleanKey, 'laravel_database_') !== false) {
                        $cleanKey = str_replace('laravel_database_', '', $cleanKey);
                    }
                    
                    // 解析 key 格式: sjtv:views:content_type:content_id[:episode_id]:total
                    // 正確的格式可能是：
                    // 1. sjtv:views:drama:2:total (沒有 episode_id)
                    // 2. sjtv:views:drama:2:3:total (有 episode_id，3 是 episode_id)
                    $keyParts = explode(':', str_replace($this->redisPrefix, '', $cleanKey));
                    
                    if (count($keyParts) >= 3) {
                        $contentType = $keyParts[0];
                        $contentId = $keyParts[1];
                        $episodeId = null;
                        
                        // 檢查是否有 episode_id
                        // 如果有 4 個部分且最後一個是 'total'，表示有 episode_id
                        if (count($keyParts) == 4 && $keyParts[3] === 'total') {
                            $episodeId = $keyParts[2];
                        }
                        // 如果只有 3 個部分且最後一個是 'total'，表示沒有 episode_id
                        elseif (count($keyParts) == 3 && $keyParts[2] === 'total') {
                            // episodeId 保持 null
                            
                            // 對於影音和節目，必須有 episode_id，否則跳過
                            if (in_array($contentType, ['drama', 'program'])) {
                                $bar->advance();
                                continue;
                            }
                        }
                        else {
                            // 格式不正確，跳過
                            $bar->advance();
                            continue;
                        }

                        // 從 Redis 取得計數
                        // ViewService 使用 Redis::incr() 時 Laravel 會自動加上 'laravel_database_' 前綴
                        // 所以我們需要移除 Redis::keys() 回傳的前綴，只保留 'sjtv:views:...' 部分
                        // 然後使用 Redis::get() 讓 Laravel 自動加上前綴來讀取

                        // 從完整 key 中提取出不含 Laravel 前綴的部分
                        // 支援多種可能的前綴：laravel_database_、sjtv_database_、laravel_cache_ 等
                        $keyWithoutPrefix = $key;
                        $prefixes = ['laravel_database_', 'sjtv_database_', 'laravel_cache_', 'laravel_'];
                        foreach ($prefixes as $prefix) {
                            $keyWithoutPrefix = str_replace($prefix, '', $keyWithoutPrefix);
                        }

                        // 使用 Laravel 的 Redis facade（會自動加上前綴）
                        $totalViews = (int) Redis::get($keyWithoutPrefix);
                        $uniqueViews = (int) Redis::get(str_replace(':total', ':unique', $keyWithoutPrefix));
                        $memberViews = (int) Redis::get(str_replace(':total', ':member', $keyWithoutPrefix));
                        $guestViews = (int) Redis::get(str_replace(':total', ':guest', $keyWithoutPrefix));
                        $todayViews = (int) Redis::get(str_replace(':total', ':daily:' . date('Ymd'), $keyWithoutPrefix));

                        // 跳過空值資料
                        if ($totalViews == 0 && $uniqueViews == 0) {
                            $bar->advance();
                            continue;
                        }

                        // 更新或建立資料庫記錄
                        DB::table('view_statistics')->updateOrInsert(
                            [
                                'content_type' => $contentType,
                                'content_id' => $contentId,
                                'episode_id' => $episodeId,
                            ],
                            [
                                'total_views' => $totalViews,
                                'unique_views' => $uniqueViews,
                                'member_views' => $memberViews,
                                'guest_views' => $guestViews,
                                'daily_views' => $todayViews,
                                'last_view_date' => now()->format('Y-m-d'),
                                'updated_at' => now(),
                                'created_at' => DB::raw('IFNULL(created_at, NOW())'),
                            ]
                        );
                    }
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine(2);
            }

            // 2. 補充：從 view_logs 計算沒有在 Redis 中的數據
            $this->info('2. 補充計算 view_logs 中未在 Redis 的數據...');
            $stats = DB::table('view_logs')
                ->select('content_type', 'content_id', DB::raw('COUNT(*) as total_views'))
                ->whereNull('episode_id')
                ->groupBy('content_type', 'content_id')
                ->get();

            if ($stats->count() > 0) {
                $bar = $this->output->createProgressBar($stats->count());
                $bar->start();

                foreach ($stats as $stat) {
                    // 檢查是否已經從 Redis 同步過
                    $existing = DB::table('view_statistics')
                        ->where('content_type', $stat->content_type)
                        ->where('content_id', $stat->content_id)
                        ->whereNull('episode_id')
                        ->first();
                    
                    if (!$existing) {
                        // 計算唯一觀看數
                        $uniqueViews = DB::table('view_logs')
                            ->where('content_type', $stat->content_type)
                            ->where('content_id', $stat->content_id)
                            ->whereNull('episode_id')
                            ->distinct()
                            ->count(DB::raw('COALESCE(user_id, ip_address)'));

                        // 建立新記錄
                        DB::table('view_statistics')->insert([
                            'content_type' => $stat->content_type,
                            'content_id' => $stat->content_id,
                            'episode_id' => null,
                            'total_views' => $stat->total_views,
                            'unique_views' => $uniqueViews,
                            'daily_views' => 0,
                            'last_view_date' => now()->format('Y-m-d'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine(2);
            }

            // 3. 清除快取
            $this->info('3. 清除相關快取...');
            $homeService = app(HomeService::class);
            $homeService->clearCache();
            $this->info('首頁快取已清除');

            // 4. 顯示統計結果
            $this->newLine();
            $this->info('=== 同步完成 ===');
            $totalRecords = DB::table('view_statistics')->count();
            $totalViews = DB::table('view_statistics')->sum('total_views');
            $totalUniqueViews = DB::table('view_statistics')->sum('unique_views');
            
            $this->table(
                ['統計項目', '數值'],
                [
                    ['總記錄數', $totalRecords],
                    ['總觀看次數', $totalViews],
                    ['總唯一觀看次數', $totalUniqueViews],
                ]
            );

            $this->info('Redis → MySQL 同步完成！');
            
        } catch (\Exception $e) {
            $this->error('同步失敗: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 從資料庫同步到 Redis
     */
    protected function syncFromDatabaseToRedis()
    {
        $this->info('=== 從資料庫同步到 Redis ===');
        $this->newLine();

        try {
            // 從 view_statistics 讀取所有記錄
            $statistics = DB::table('view_statistics')->get();
            
            $this->info("找到 {$statistics->count()} 筆統計記錄");
            
            if ($statistics->count() > 0) {
                $bar = $this->output->createProgressBar($statistics->count());
                $bar->start();

                foreach ($statistics as $stat) {
                    $baseKey = $this->redisPrefix . "{$stat->content_type}:{$stat->content_id}";
                    if ($stat->episode_id) {
                        $baseKey .= ":{$stat->episode_id}";
                    }

                    // 設定 Redis 快取
                    Redis::setex($baseKey . ':total', 86400, $stat->total_views);
                    Redis::setex($baseKey . ':unique', 86400, $stat->unique_views);
                    
                    // 如果是今天的數據，也設定日快取
                    if ($stat->last_view_date == now()->format('Y-m-d')) {
                        $todayKey = $baseKey . ':daily:' . date('Ymd');
                        Redis::setex($todayKey, 86400, $stat->daily_views);
                    }
                    
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine(2);
            }

            $this->info('MySQL → Redis 同步完成！');
            
        } catch (\Exception $e) {
            $this->error('同步失敗: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 從 view_logs 重新計算所有統計
     */
    protected function recalculateFromViewLogs()
    {
        $this->info('=== 從 view_logs 重新計算所有統計 ===');
        $this->newLine();

        try {
            // 1. 清空 view_statistics 表
            // 如果是 no-interaction 模式，直接執行
            $shouldProceed = !$this->input->isInteractive() || $this->confirm('這將清空並重建 view_statistics 表，確定要繼續嗎？');
            
            if ($shouldProceed) {
                DB::table('view_statistics')->truncate();
                $this->info('已清空 view_statistics 表');
            } else {
                $this->info('操作已取消');
                return;
            }

            // 2. 從 view_logs 重新計算統計
            $this->info('開始重新計算統計數據...');
            
            // 計算總觀看數、唯一觀看數、會員觀看數、訪客觀看數
            $stats = DB::table('view_logs')
                ->select(
                    'content_type',
                    'content_id',
                    'episode_id',
                    DB::raw('COUNT(*) as total_views'),
                    DB::raw('COUNT(DISTINCT COALESCE(user_id, ip_address)) as unique_views'),
                    DB::raw('SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as member_views'),
                    DB::raw('SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as guest_views'),
                    DB::raw('MAX(created_at) as last_view_time')
                )
                ->groupBy('content_type', 'content_id', 'episode_id')
                ->get();

            $this->info("找到 {$stats->count()} 個內容需要統計");

            if ($stats->count() > 0) {
                $bar = $this->output->createProgressBar($stats->count());
                $bar->start();

                foreach ($stats as $stat) {
                    // 計算今日觀看數
                    $todayViews = DB::table('view_logs')
                        ->where('content_type', $stat->content_type)
                        ->where('content_id', $stat->content_id)
                        ->when($stat->episode_id, function ($query) use ($stat) {
                            return $query->where('episode_id', $stat->episode_id);
                        })
                        ->when(!$stat->episode_id, function ($query) {
                            return $query->whereNull('episode_id');
                        })
                        ->whereDate('created_at', now()->format('Y-m-d'))
                        ->count();

                    // 插入或更新統計記錄（避免重複 key 錯誤）
                    DB::table('view_statistics')->updateOrInsert(
                        [
                            'content_type' => $stat->content_type,
                            'content_id' => $stat->content_id,
                            'episode_id' => $stat->episode_id,
                        ],
                        [
                            'total_views' => $stat->total_views,
                            'unique_views' => $stat->unique_views,
                            'member_views' => $stat->member_views ?? 0,
                            'guest_views' => $stat->guest_views ?? 0,
                            'daily_views' => $todayViews,
                            'last_view_date' => date('Y-m-d', strtotime($stat->last_view_time)),
                            'updated_at' => now(),
                        ]
                    );

                    // 同時更新 Redis
                    $baseKey = $this->redisPrefix . "{$stat->content_type}:{$stat->content_id}";
                    if ($stat->episode_id) {
                        $baseKey .= ":{$stat->episode_id}";
                    }

                    Redis::setex($baseKey . ':total', 86400, $stat->total_views);
                    Redis::setex($baseKey . ':unique', 86400, $stat->unique_views);
                    Redis::setex($baseKey . ':member', 86400, $stat->member_views ?? 0);
                    Redis::setex($baseKey . ':guest', 86400, $stat->guest_views ?? 0);

                    if ($todayViews > 0) {
                        $todayKey = $baseKey . ':daily:' . date('Ymd');
                        Redis::setex($todayKey, 86400, $todayViews);
                    }

                    $bar->advance();
                }

                $bar->finish();
                $this->newLine(2);
            }

            // 3. 清除快取
            $this->info('清除相關快取...');
            $homeService = app(HomeService::class);
            $homeService->clearCache();

            // 4. 顯示結果
            $this->newLine();
            $this->info('=== 重新計算完成 ===');
            $totalRecords = DB::table('view_statistics')->count();

            // 各類別統計
            $categoryStats = DB::table('view_statistics')
                ->select(
                    'content_type',
                    DB::raw('SUM(total_views) as total_views'),
                    DB::raw('SUM(member_views) as member_views')
                )
                ->groupBy('content_type')
                ->get()
                ->keyBy('content_type');

            $this->table(
                ['內容類型', '總觀看次數', '會員觀看次數'],
                $categoryStats->map(function ($stat) {
                    return [$stat->content_type, $stat->total_views, $stat->member_views];
                })->values()->toArray()
            );

            $this->info('view_statistics 表已從 view_logs 重新計算完成！');

            // 寫入日誌（各類別統計）
            $logData = ['total_records' => $totalRecords];
            foreach ($categoryStats as $type => $stat) {
                $logData[$type] = [
                    'total_views' => (int) $stat->total_views,
                    'member_views' => (int) $stat->member_views,
                ];
            }
            Log::info('View statistics recalculation completed', $logData);

        } catch (\Exception $e) {
            $this->error('重新計算失敗: ' . $e->getMessage());
            Log::error('View statistics recalculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}