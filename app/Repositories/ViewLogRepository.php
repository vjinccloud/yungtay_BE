<?php

namespace App\Repositories;

use App\Models\ViewLog;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ViewLogRepository extends BaseRepository
{
    public function __construct(ViewLog $model)
    {
        parent::__construct($model);
    }

    /**
     * 記錄觀看
     */
    public function recordView(array $data): ViewLog
    {
        return $this->create([
            'content_type' => $data['content_type'],
            'content_id' => $data['content_id'],
            'episode_id' => $data['episode_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'] ?? null,
        ]);
    }

    /**
     * 檢查是否為唯一觀看（同一用戶/IP 在指定時間內）
     */
    public function isUniqueView(string $contentType, int $contentId, ?int $episodeId = null, ?int $userId = null, string $ipAddress = null, int $timeWindow = 3600): bool
    {
        $query = $this->model->newQuery()
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->where('created_at', '>', now()->subSeconds($timeWindow));

        if ($episodeId) {
            $query->where('episode_id', $episodeId);
        }

        // 優先使用用戶ID檢查，其次使用IP
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        } else {
            return true; // 無法識別身份，視為唯一觀看
        }

        return !$query->exists();
    }

    /**
     * 取得內容總觀看數
     */
    public function getTotalViews(string $contentType, int $contentId, ?int $episodeId = null): int
    {
        $query = $this->model->newQuery()
            ->where('content_type', $contentType)
            ->where('content_id', $contentId);

        if ($episodeId) {
            $query->where('episode_id', $episodeId);
        }

        return $query->count();
    }

    /**
     * 取得內容唯一觀看數（基於用戶ID和IP去重）
     */
    public function getUniqueViews(string $contentType, int $contentId, ?int $episodeId = null): int
    {
        $query = $this->model->newQuery()
            ->where('content_type', $contentType)
            ->where('content_id', $contentId);

        if ($episodeId) {
            $query->where('episode_id', $episodeId);
        }

        // 使用子查詢去重計算
        $uniqueUsers = $query->clone()->whereNotNull('user_id')
            ->distinct('user_id')->count('user_id');

        $uniqueIps = $query->clone()->whereNull('user_id')
            ->distinct('ip_address')->count('ip_address');

        return $uniqueUsers + $uniqueIps;
    }

    /**
     * 取得指定時間範圍的觀看數
     */
    public function getViewsByDateRange(string $contentType, int $contentId, Carbon $startDate, Carbon $endDate, ?int $episodeId = null): int
    {
        $query = $this->model->newQuery()
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($episodeId) {
            $query->where('episode_id', $episodeId);
        }

        return $query->count();
    }

    /**
     * 取得今日觀看數
     */
    public function getTodayViews(string $contentType, int $contentId, ?int $episodeId = null): int
    {
        return $this->getViewsByDateRange(
            $contentType, 
            $contentId, 
            now()->startOfDay(), 
            now()->endOfDay(),
            $episodeId
        );
    }



    /**
     * 應用時間範圍篩選（公共方法，保持向後相容）
     */
    protected function applyTimeRangeFilter($query, string $timeRange)
    {
        switch ($timeRange) {
            case 'one_month':
                return $query->where('created_at', '>=', now()->subDays(30));
            case 'three_months':
                return $query->where('created_at', '>=', now()->subDays(90));
            case 'six_months':
                return $query->where('created_at', '>=', now()->subDays(180));
            case 'one_year':
                return $query->where('created_at', '>=', now()->subDays(365));
            case 'all':
            default:
                return $query; // 不加時間限制
        }
    }

    /**
     * 取得指定內容類型和時間區間的觀看數（儀表板用）
     */
    public function getViewCountByPeriod(string $contentType, string $period): int
    {
        try {
            $query = $this->model->newQuery()->where('content_type', $contentType);

            switch ($period) {
                case 'today':
                    $query->whereDate('created_at', now()->format('Y-m-d'));
                    break;
                case 'week':
                    $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    ]);
                    break;
                case 'total':
                    // 不加任何時間限制
                    break;
            }

            return $query->count();
        } catch (\Exception $e) {
            \Log::error('取得觀看數統計失敗', [
                'content_type' => $contentType,
                'period' => $period,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * 取得熱門內容（基於觀看數）
     */
    public function getPopularContent(string $contentType, int $limit = 10, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->newQuery()
            ->select('content_id', DB::raw('COUNT(*) as view_count'))
            ->where('content_type', $contentType);
        
        // 處理不同的時間範圍
        if ($days === 0) {
            // 今日：只查詢今天的資料
            $query->whereDate('created_at', now()->format('Y-m-d'));
        } elseif ($days < 9999) {
            // 其他時間範圍：使用 subDays
            $query->where('created_at', '>=', now()->subDays($days));
        }
        // 如果 days >= 9999（total），不加任何時間限制
        
        return $query->groupBy('content_id')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 取得內容觀看趨勢（按日期分組）
     */
    public function getViewTrend(string $contentType, int $contentId, int $days = 30, ?int $episodeId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->newQuery()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as views'))
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc');

        if ($episodeId) {
            $query->where('episode_id', $episodeId);
        }

        return $query->get();
    }

    /**
     * 取得觀看數統計（依內容類型）
     */
    public function getViewStatsByContentType(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()
            ->select('content_type', DB::raw('COUNT(*) as total_views'), DB::raw('COUNT(DISTINCT user_id) as unique_users'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('content_type')
            ->orderBy('total_views', 'desc')
            ->get();
    }


    /**
     * 清理舊記錄
     */
    public function cleanOldRecords(int $daysToKeep = 90): int
    {
        return $this->model->newQuery()
            ->where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }


    /**
     * 取得時段觀看分布
     */
    public function getViewsByHour(string $contentType = null, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->newQuery()
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as views'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour', 'asc');

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        return $query->get();
    }

    /**
     * 取得會員觀看歷史（去重版本 - 每個內容只顯示最新的觀看記錄）
     */
    public function getUserViewHistory($userId, $filters = [], $perPage = 16)
    {
        // 使用簡潔的方式：先取得每組的最新ID，再查詢完整資料
        $latestViewIds = $this->model->select(DB::raw('MAX(id) as latest_id'))
            ->where('user_id', $userId)
            ->groupBy('content_type', 'content_id', DB::raw('COALESCE(episode_id, 0)'));

        // 套用篩選條件
        if (!empty($filters['content_type'])) {
            $latestViewIds->where('content_type', $filters['content_type']);
        }

        $this->applyTimeFilter($latestViewIds, $filters['time_range'] ?? 'all');

        // 取得最新ID列表
        $viewIds = $latestViewIds->pluck('latest_id');

        // 主查詢：根據ID列表取得完整資料
        $query = $this->model->with([
            'article.image_thumbnail',      // 新聞縮圖
            'drama.posterDesktop',          // 影音海報
            'program.posterDesktop',        // 節目海報
            'live.images',                  // 直播圖片
            'radio.image',                  // 廣播圖片
            'dramaEpisode.thumbnail',       // 影音集數縮圖
            'programEpisode.thumbnail'      // 節目集數縮圖
        ])
        ->whereIn('id', $viewIds)
        ->orderBy('created_at', 'desc');
        
        // 分頁並格式化
        $paginated = $query->paginate($perPage);
        
        // 使用 getCollection() 和 setCollection() 來保持分頁資訊
        $filteredData = $paginated->getCollection()
            ->map(function ($viewLog) {
                return $this->formatViewLogForFrontend($viewLog);
            })
            ->filter() // 過濾掉已刪除的內容
            ->values(); // 重新索引
        
        // 重新設定過濾後的資料，保持分頁結構
        $paginated->setCollection($filteredData);
        
        return $paginated;
    }


    /**
     * 格式化觀看紀錄（參考 UserCollectionRepository 優雅模式）
     */
    protected function formatViewLogForFrontend($viewLog)
    {
        $content = $this->getRelatedContent($viewLog);
        
        if (!$content) {
            return null; // 內容已被刪除，filter() 會過濾掉
        }
        
        $locale = app()->getLocale();
        
        // 統一的回傳格式
        return [
            'id' => $viewLog->id,
            'content_type' => $viewLog->content_type,
            'content_id' => $viewLog->content_id,
            'episode_id' => $viewLog->episode_id,
            'title' => $this->getContentTitle($content, $viewLog, $locale),
            'episode_info' => $this->getEpisodeInfo($viewLog), // 影音和節目的集數資訊
            'image' => $this->getContentImage($content, $viewLog),
            'url' => $this->getContentUrl($content, $viewLog),
            'viewed_at' => $viewLog->created_at->format('Y-m-d H:i:s'),
            'viewed_date' => $viewLog->created_at->format('Y.m.d'),
        ];
    }

    /**
     * 取得相關內容（利用 Model 關聯避免 N+1）
     */
    private function getRelatedContent($viewLog)
    {
        switch ($viewLog->content_type) {
            case 'article':
                return $viewLog->article;
            case 'drama':
                return $viewLog->drama;
            case 'program':
                return $viewLog->program;
            case 'live':
                return $viewLog->live;
            case 'radio':
                return $viewLog->radio;
            default:
                return null;
        }
    }

    /**
     * 應用時間篩選（私有方法 - 支援去重查詢）
     */
    private function applyTimeFilter($query, $timeRange)
    {
        switch ($timeRange) {
            case 'today':
                $query->whereDate('created_at', now()->format('Y-m-d'));
                break;
            case 'week':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case 'one_month':
                $query->where('created_at', '>=', now()->subDays(30));
                break;
            case 'three_months':
                $query->where('created_at', '>=', now()->subDays(90));
                break;
            case 'six_months':
                $query->where('created_at', '>=', now()->subDays(180));
                break;
            case 'one_year':
                $query->where('created_at', '>=', now()->subDays(365));
                break;
            case 'all':
            default:
                // 不限制時間
                break;
        }
    }

    /**
     * 取得內容標題
     */
    private function getContentTitle($content, $viewLog, $locale)
    {
        if (!$content) {
            return match($viewLog->content_type) {
                'article' => '已刪除的新聞',
                'drama' => '已刪除的影音',
                'program' => '已刪除的節目',
                'live' => '已刪除的直播',
                'radio' => '已刪除的廣播',
                default => '已刪除的內容'
            };
        }
        
        // 安全處理多語言標題
        if (method_exists($content, 'getTranslation')) {
            try {
                // 檢查 title 是否在翻譯屬性中
                $translatable = property_exists($content, 'translatable') ? $content->translatable : [];
                if (is_array($translatable) && in_array('title', $translatable)) {
                    return $content->getTranslation('title', $locale) ?? $content->title ?? '';
                }
            } catch (\Exception $e) {
                // 翻譯失敗時回退到普通屬性
            }
        }
        
        return $content->title ?? '';
    }


    /**
     * 取得內容圖片（重點實作 - 圖片優先級規則）
     */
    private function getContentImage($content, $viewLog)
    {
        if (!$content) {
            return asset('frontend/images/default.webp');
        }
        
        switch ($viewLog->content_type) {
            case 'drama':
                // 優先使用集數縮圖
                if ($viewLog->episode_id && $viewLog->dramaEpisode && $viewLog->dramaEpisode->thumbnail) {
                    return asset($this->resolveImageUrl($viewLog->dramaEpisode->thumbnail->path));
                }
                // 沒有集數縮圖則使用影音海報
                if ($content->posterDesktop) {
                    return asset($this->resolveImageUrl($content->posterDesktop->path));
                }
                return asset('frontend/images/default.webp');
            
            case 'program':
                // 優先使用集數縮圖
                if ($viewLog->episode_id && $viewLog->programEpisode && $viewLog->programEpisode->thumbnail) {
                    return asset($this->resolveImageUrl($viewLog->programEpisode->thumbnail->path));
                }
                // 沒有集數縮圖則使用節目海報
                if ($content->posterDesktop) {
                    return asset($this->resolveImageUrl($content->posterDesktop->path));
                }
                return asset('frontend/images/default.webp');
            
            case 'live':
                // 使用 Live Model 的縮圖處理（已有 YouTube 縮圖邏輯）
                $thumbnail = $content->thumbnail; // 這會呼叫 getThumbnailAttribute()
                return $thumbnail ?: asset('frontend/images/default.webp');
            
            case 'article':
                // 新聞只使用縮圖
                return $content->image_thumbnail ? asset($this->resolveImageUrl($content->image_thumbnail->path)) : asset('frontend/images/default.webp');
            
            case 'radio':
                // 廣播使用 image 關聯
                return $content->image ? asset($this->resolveImageUrl($content->image->path)) : asset('frontend/images/default.webp');
            
            default:
                return asset('frontend/images/default.webp');
        }
    }

    /**
     * 取得內容連結
     */
    private function getContentUrl($content, $viewLog)
    {
        if (!$content) {
            return '#';
        }
        
        switch ($viewLog->content_type) {
            case 'article':
                return route('articles.show', $content->id);
            
            case 'drama':
                if ($viewLog->episode_id) {
                    return route('drama.video.show', ['dramaId' => $content->id, 'episodeId' => $viewLog->episode_id]);
                }
                return route('drama.videos.index', $content->id);
            
            case 'program':
                if ($viewLog->episode_id) {
                    return route('program.video.show', ['programId' => $content->id, 'episodeId' => $viewLog->episode_id]);
                }
                return route('program.videos.index', $content->id);
            
            case 'live':
                return route('live.index', $content->id);
            
            case 'radio':
                return route('radio.show', $content->id);
            
            default:
                return '#';
        }
    }


    /**
     * 取得集數資訊（僅限影音和節目）
     */
    private function getEpisodeInfo($viewLog)
    {
        // 只有影音和節目需要集數資訊
        if (!in_array($viewLog->content_type, ['drama', 'program']) || !$viewLog->episode_id) {
            return null;
        }
        
        $episode = $viewLog->content_type === 'drama' ? 
            $viewLog->dramaEpisode : $viewLog->programEpisode;
            
        if ($episode) {
            // 直接使用 EpisodeModelTrait 的 getEpisodeTitle 方法
            // 該方法會自動根據當前語系回傳對應格式（中文：第X集，英文：Episode X）
            return $episode->getEpisodeTitle();
        }
        
        // 如果沒有有效的集數資訊，返回 null
        return null;
    }

    /**
     * 取得會員觀看次數（聚合所有集數）
     */
    public function getMemberViewsByContent(string $contentType, int $contentId): int
    {
        return $this->model
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->whereNotNull('user_id')
            ->count();
    }

    /**
     * 取得訪客觀看次數（聚合所有集數）
     */
    public function getGuestViewsByContent(string $contentType, int $contentId): int
    {
        return $this->model
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->whereNull('user_id')
            ->count();
    }
}