<?php

namespace App\Repositories;

use App\Models\ViewStatistic;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ViewStatisticRepository extends BaseRepository
{
    public function __construct(ViewStatistic $model)
    {
        parent::__construct($model);
    }

    /**
     * 查找或建立統計記錄
     */
    public function findOrCreateStatistic(string $contentType, int $contentId, ?int $episodeId = null): ViewStatistic
    {
        $conditions = [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'episode_id' => $episodeId
        ];

        return $this->model->firstOrCreate($conditions, array_merge($conditions, [
            'total_views' => 0,
            'unique_views' => 0,
            'daily_views' => 0,
            'last_view_date' => now()->format('Y-m-d')
        ]));
    }

    /**
     * 更新統計數據
     */
    public function updateStatistic(ViewStatistic $statistic, bool $isUnique = false, int $totalIncrement = 1, bool $isMember = false): ViewStatistic
    {
        $updateData = [
            'total_views' => $statistic->total_views + $totalIncrement,
            'daily_views' => $statistic->daily_views + $totalIncrement,
            'last_view_date' => now()->format('Y-m-d')
        ];

        if ($isUnique) {
            $updateData['unique_views'] = $statistic->unique_views + 1;
        }

        // 更新會員/訪客觀看次數
        if ($isMember) {
            $updateData['member_views'] = $statistic->member_views + $totalIncrement;
        } else {
            $updateData['guest_views'] = $statistic->guest_views + $totalIncrement;
        }

        $statistic->update($updateData);

        return $statistic->fresh();
    }

    /**
     * 批量重置日觀看數
     */
    public function resetDailyViews(): int
    {
        return $this->model->newQuery()->update(['daily_views' => 0]);
    }

    /**
     * 重新計算統計數據（從觀看記錄同步）
     */
    public function recalculateFromLogs(string $contentType, int $contentId, ?int $episodeId = null): ViewStatistic
    {
        $logRepository = app(\App\Repositories\ViewLogRepository::class);
        
        $totalViews = $logRepository->getTotalViews($contentType, $contentId, $episodeId);
        $uniqueViews = $logRepository->getUniqueViews($contentType, $contentId, $episodeId);
        $todayViews = $logRepository->getTodayViews($contentType, $contentId, $episodeId);

        $statistic = $this->findOrCreateStatistic($contentType, $contentId, $episodeId);
        
        $statistic->update([
            'total_views' => $totalViews,
            'unique_views' => $uniqueViews,
            'daily_views' => $todayViews,
            'last_view_date' => now()->format('Y-m-d')
        ]);

        return $statistic;
    }

    /**
     * 取得熱門內容排行
     */
    public function getPopularContent(string $contentType, int $limit = 10, string $orderBy = 'total_views'): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->newQuery()
            ->where('content_type', $contentType)
            ->orderBy($orderBy, 'desc')
            ->limit($limit);

        // 載入相關關聯
        switch ($contentType) {
            case 'drama':
                $query->with('drama:id,title');
                break;
            case 'program':
                $query->with('program:id,title');
                break;
            case 'article':
                $query->with('article:id,title');
                break;
            case 'live':
                $query->with('live:id,title');
                break;
            case 'radio':
                $query->with('radio:id,title');
                break;
        }

        return $query->get();
    }

    /**
     * 取得內容統計排行（跨類型）
     */
    public function getCrossTypeRanking(int $limit = 20, string $orderBy = 'total_views'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()
            ->with(['drama:id,title', 'program:id,title', 'article:id,title', 'live:id,title', 'radio:id,title'])
            ->orderBy($orderBy, 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 取得今日熱門
     */
    public function getTodayPopular(string $contentType = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->newQuery()
            ->where('daily_views', '>', 0)
            ->whereDate('last_view_date', now()->format('Y-m-d'))
            ->orderBy('daily_views', 'desc')
            ->limit($limit);

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        return $query->get();
    }

    /**
     * 取得觀看數統計摘要
     */
    public function getViewsSummary(string $contentType = null): array
    {
        $query = $this->model->newQuery();

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        $result = $query->selectRaw('
            SUM(total_views) as total_views,
            SUM(unique_views) as total_unique_views,
            SUM(daily_views) as total_daily_views,
            AVG(total_views) as avg_views,
            COUNT(*) as content_count
        ')->first();

        return [
            'total_views' => (int) $result->total_views,
            'total_unique_views' => (int) $result->total_unique_views,
            'total_daily_views' => (int) $result->total_daily_views,
            'average_views' => round($result->avg_views, 2),
            'content_count' => (int) $result->content_count
        ];
    }

    /**
     * 取得內容類型統計
     */
    public function getContentTypeStats(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()
            ->select('content_type')
            ->selectRaw('
                SUM(total_views) as total_views,
                SUM(unique_views) as unique_views,
                SUM(daily_views) as daily_views,
                COUNT(*) as content_count
            ')
            ->groupBy('content_type')
            ->orderBy('total_views', 'desc')
            ->get();
    }

    /**
     * 取得觀看成長統計
     */
    public function getGrowthStats(int $days = 7): array
    {
        $today = $this->model->newQuery()
            ->whereDate('last_view_date', now()->format('Y-m-d'))
            ->sum('daily_views');

        $yesterday = $this->model->newQuery()
            ->whereDate('last_view_date', now()->subDay()->format('Y-m-d'))
            ->sum('daily_views');

        $weekAgo = $this->model->newQuery()
            ->whereDate('last_view_date', now()->subDays(7)->format('Y-m-d'))
            ->sum('daily_views');

        return [
            'today' => (int) $today,
            'yesterday' => (int) $yesterday,
            'week_ago' => (int) $weekAgo,
            'daily_growth_rate' => $yesterday > 0 ? round((($today - $yesterday) / $yesterday) * 100, 2) : 0,
            'weekly_growth_rate' => $weekAgo > 0 ? round((($today - $weekAgo) / $weekAgo) * 100, 2) : 0
        ];
    }

    /**
     * 取得最近更新的內容
     */
    public function getRecentlyUpdated(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()
            ->with(['drama:id,title', 'program:id,title', 'article:id,title', 'live:id,title', 'radio:id,title'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 取得零觀看內容
     */
    public function getZeroViewsContent(string $contentType = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->model->newQuery()
            ->where('total_views', 0);

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        return $query->get();
    }

    /**
     * 批量更新統計（用於定時任務）
     */
    public function batchUpdateFromLogs(array $contentIds = [], string $contentType = null): int
    {
        $query = $this->model->newQuery();

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        if (!empty($contentIds)) {
            $query->whereIn('content_id', $contentIds);
        }

        $statistics = $query->get();
        $updatedCount = 0;

        foreach ($statistics as $statistic) {
            try {
                $this->recalculateFromLogs(
                    $statistic->content_type,
                    $statistic->content_id,
                    $statistic->episode_id
                );
                $updatedCount++;
            } catch (\Exception $e) {
                \Log::error('Failed to update statistic', [
                    'statistic_id' => $statistic->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $updatedCount;
    }

    /**
     * 分頁查詢統計記錄
     */
    public function paginate($perPage = 15, $sortColumn = 'total_views', $sortDirection = 'desc', $filters = [])
    {
        $query = $this->model->newQuery()
            ->with(['drama:id,title', 'program:id,title', 'article:id,title', 'live:id,title', 'radio:id,title']);

        // 應用篩選條件
        if (!empty($filters['content_type'])) {
            $query->where('content_type', $filters['content_type']);
        }

        if (!empty($filters['min_views'])) {
            $query->where('total_views', '>=', $filters['min_views']);
        }

        if (!empty($filters['max_views'])) {
            $query->where('total_views', '<=', $filters['max_views']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('last_view_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('last_view_date', '<=', $filters['date_to']);
        }

        // 排序
        $allowedSortColumns = ['total_views', 'unique_views', 'daily_views', 'last_view_date', 'updated_at'];
        if ($sortColumn && $sortColumn !== 'null' && in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection === 'null' ? 'desc' : $sortDirection);
        } else {
            $query->orderBy('total_views', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * 取得內容的總觀看次數（聚合所有集數）
     */
    public function getTotalViewsByContent(string $contentType, int $contentId): int
    {
        return $this->model
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->sum('total_views');
    }

    /**
     * 取得集數觀看統計資料（用於 DataTable）
     */
    public function getEpisodeViewStats(string $contentType, int $contentId, array $filters = [], int $perPage = 25, int $page = 1): array
    {
        // 取得對應的 Episode Model
        $episodeModel = $this->getEpisodeModel($contentType);

        if (!$episodeModel) {
            return [
                'data' => [],
                'total' => 0,
            ];
        }

        // 查詢集數和觀看統計（view_statistics 已有統計好的資料，不需要 GROUP BY）
        $tableName = $this->getEpisodeTableName($contentType);
        $query = DB::table($tableName)
            ->where($contentType . '_id', $contentId);

        // 季數篩選
        if (!empty($filters['season'])) {
            $query->where($tableName . '.season', $filters['season']);
        }

        $query->leftJoin('view_statistics', function ($join) use ($contentType, $tableName) {
                $join->on('view_statistics.episode_id', '=', $tableName . '.id')
                    ->where('view_statistics.content_type', '=', $contentType);
            })
            ->select(
                $tableName . '.id as episode_id',
                $tableName . '.season as season_number',
                $tableName . '.seq as episode_number',
                DB::raw('COALESCE(view_statistics.total_views, 0) as total_views'),
                DB::raw('COALESCE(view_statistics.unique_views, 0) as unique_views'),
                DB::raw('COALESCE(view_statistics.member_views, 0) as member_views'),
                DB::raw('COALESCE(view_statistics.guest_views, 0) as guest_views')
            )
            ->orderBy($tableName . '.season', 'asc')
            ->orderBy($tableName . '.seq', 'asc');

        // 計算總數
        $total = $query->count();

        // 分頁
        $offset = ($page - 1) * $perPage;
        $items = $query->skip($offset)->take($perPage)->get();

        // 格式化資料
        $data = $items->map(function ($item) {
            return [
                'season_number' => $item->season_number ?? '-',
                'episode_number' => $item->episode_number ?? '-',
                'total_views' => (int) $item->total_views,
                'unique_views' => (int) $item->unique_views,
                'member_views' => (int) $item->member_views,
                'guest_views' => (int) $item->guest_views,
            ];
        })->toArray();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    /**
     * 取得對應的 Episode Model
     */
    private function getEpisodeModel(string $contentType): ?string
    {
        $models = [
            'drama' => \App\Models\DramaEpisode::class,
            'program' => \App\Models\ProgramEpisode::class,
        ];

        return $models[$contentType] ?? null;
    }

    /**
     * 取得對應的 Episode 資料表名稱
     */
    private function getEpisodeTableName(string $contentType): string
    {
        $tables = [
            'drama' => 'drama_episodes',
            'program' => 'program_episodes',
        ];

        return $tables[$contentType] ?? $contentType . '_episodes';
    }

    /**
     * 取得內容類型統計（含收藏數）
     * 用於儀表板觀看數統計表格
     *
     * @return array
     */
    public function getContentTypeStatsWithCollections(): array
    {

        // 1. 取得觀看數統計（從 view_statistics 聚合）
        $viewStats = DB::table('view_statistics')
            ->select('content_type')
            ->selectRaw('SUM(member_views) as member_views')
            ->selectRaw('SUM(guest_views) as guest_views')
            ->selectRaw('SUM(total_views) as total_views')
            ->groupBy('content_type')
            ->get()
            ->keyBy('content_type');

        // 2. 取得收藏數統計（從 user_collections 聚合）
        $collectionStats = DB::table('user_collections')
            ->select('content_type')
            ->selectRaw('COUNT(DISTINCT user_id) as collection_count')
            ->groupBy('content_type')
            ->get()
            ->keyBy('content_type');

        // 3. content_type 映射表（user_collections 使用複數形式）
        $collectionTypeMap = [
            'article' => 'articles',  // 新聞：view_statistics 用 article，user_collections 用 articles
            'drama' => 'drama',
            'program' => 'program',
            'live' => 'live',
            'radio' => 'radio',
        ];

        // 4. 合併資料並確保所有內容類型都有數據
        $contentTypes = ['article', 'drama', 'program', 'live', 'radio'];
        $result = [];

        foreach ($contentTypes as $type) {
            // 取得收藏數時使用映射後的類型名稱
            $collectionType = $collectionTypeMap[$type];

            $memberViews = isset($viewStats[$type]) ? (int) $viewStats[$type]->member_views : 0;
            $guestViews = isset($viewStats[$type]) ? (int) $viewStats[$type]->guest_views : 0;
            $totalViews = isset($viewStats[$type]) ? (int) $viewStats[$type]->total_views : 0;
            $collectionCount = $type === 'live' ? 0 : (isset($collectionStats[$collectionType]) ? (int) $collectionStats[$collectionType]->collection_count : 0);

            $item = [
                'type' => $type,
                'member_views' => $memberViews,
                'guest_views' => $guestViews,
                'total_views' => $totalViews,
                'collection_count' => $collectionCount,
            ];

            $result[] = $item;

        }

        return $result;
    }
}