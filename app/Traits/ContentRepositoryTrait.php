<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * 內容 Repository 共用邏輯
 * 用於 DramaRepository 和 ProgramRepository
 * 處理圖片、分頁、搜尋等共用功能
 */
trait ContentRepositoryTrait
{
    /**
     * 取得內容類型（drama 或 program）
     * @return string
     */
    abstract protected function getContentType(): string;

    /**
     * 取得影片關聯欄位（drama_id 或 program_id）
     * @return string
     */
    abstract protected function getVideoIdField(): string;

    /**
     * 儲存內容（含圖片處理）
     */
    public function save(array $attributes = [], $id = null)
    {
        // 1. 先從 attributes 中拆出圖片資料
        $posterDesktop = null;
        $posterMobile = null;
        $bannerDesktop = null;
        $bannerMobile = null;

        if (array_key_exists('poster_desktop', $attributes)) {
            if (!is_null($attributes['poster_desktop'])) {
                $posterDesktop = $attributes['poster_desktop'];
            }
            unset($attributes['poster_desktop']);
        }

        if (array_key_exists('poster_mobile', $attributes)) {
            if (!is_null($attributes['poster_mobile'])) {
                $posterMobile = $attributes['poster_mobile'];
            }
            unset($attributes['poster_mobile']);
        }

        if (array_key_exists('banner_desktop', $attributes)) {
            if (!is_null($attributes['banner_desktop'])) {
                $bannerDesktop = $attributes['banner_desktop'];
            }
            unset($attributes['banner_desktop']);
        }

        if (array_key_exists('banner_mobile', $attributes)) {
            if (!is_null($attributes['banner_mobile'])) {
                $bannerMobile = $attributes['banner_mobile'];
            }
            unset($attributes['banner_mobile']);
        }

        //自動設定建立者和修改者
        $adminId = auth('admin')->id();

        if (!$id) {
            // 新增時設定建立者
            $attributes['created_by'] = $adminId;
        }

        // 每次都更新修改者
        $attributes['updated_by'] = $adminId;

        // 2. 儲存主資料
        $content = parent::save($attributes, $id);

        // 3. 圖片處理
        $this->handleImages($content, $posterDesktop, $posterMobile, $bannerDesktop, $bannerMobile);

        // 4. 如果是新建立的內容，更新相關影片的 parent_id
        if (!$id && $content) {
            $this->updateVideosWithContentId($content->id);
        }

        return $content;
    }

    /**
     * 處理圖片儲存
     */
    protected function handleImages($content, $posterDesktop, $posterMobile, $bannerDesktop, $bannerMobile)
    {
        $contentType = $this->getContentType();

        // 處理桌機海報（860×485，不會超過壓縮限制，但為統一性仍明確設定）
        if ($posterDesktop) {
            $imageData = ['poster_desktop' => $posterDesktop];
            $oldImage = $content->images()->where('image_type', 'poster_desktop')->first();

            $this->imgRepository->saveSlimFile(
                $imageData,              // $imageData
                $content,                // $class (model)
                $oldImage,               // $image (舊圖片)
                "{$contentType}s/posters", // $path
                false,                   // $compress（不壓縮，保持原尺寸）
                false                    // $generateThumbnail
            );
        }

        // 處理手機海報（200×240，不會超過壓縮限制，但為統一性仍明確設定）
        if ($posterMobile) {
            $imageData = ['poster_mobile' => $posterMobile];
            $oldImage = $content->images()->where('image_type', 'poster_mobile')->first();

            $this->imgRepository->saveSlimFile(
                $imageData,              // $imageData
                $content,                // $class (model)
                $oldImage,               // $image (舊圖片)
                "{$contentType}s/posters", // $path
                false,                   // $compress（不壓縮，保持原尺寸）
                false                    // $generateThumbnail
            );
        }

        // 處理桌機橫幅（1915×798，會超過 1600px 壓縮限制，必須關閉壓縮）
        if ($bannerDesktop) {
            $imageData = ['banner_desktop' => $bannerDesktop];
            $oldImage = $content->images()->where('image_type', 'banner_desktop')->first();

            $this->imgRepository->saveSlimFile(
                $imageData,              // $imageData
                $content,                // $class (model)
                $oldImage,               // $image (舊圖片)
                "{$contentType}s/banners", // $path
                false,                   // $compress（不壓縮，保持 1915×798 原尺寸）
                false                    // $generateThumbnail
            );
        }

        // 處理手機橫幅（430×240，不會超過壓縮限制，但為統一性仍明確設定）
        if ($bannerMobile) {
            $imageData = ['banner_mobile' => $bannerMobile];
            $oldImage = $content->images()->where('image_type', 'banner_mobile')->first();

            $this->imgRepository->saveSlimFile(
                $imageData,              // $imageData
                $content,                // $class (model)
                $oldImage,               // $image (舊圖片)
                "{$contentType}s/banners", // $path
                false,                   // $compress（不壓縮，保持原尺寸）
                false                    // $generateThumbnail
            );
        }
    }

    /**
     * 更新影片的內容 ID
     */
    protected function updateVideosWithContentId($contentId)
    {
        $videoModel = $this->getContentType() === 'drama' 
            ? \App\Models\DramaEpisode::class 
            : \App\Models\ProgramEpisode::class;
        
        $videoModel::whereNull($this->getVideoIdField())
            ->update([$this->getVideoIdField() => $contentId]);
    }

    /**
     * 分頁查詢（共用邏輯）
     */
    public function paginate($perPage = 10, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        $query = $this->model->newQuery()
            ->with(['category', 'subcategory', 'posterDesktop', 'posterMobile']);

        // 使用 Model 的 scopeFilter 方法來處理所有篩選邏輯
        // 這樣可以確保 MediaContentTrait 中的日期篩選邏輯被執行
        if (!empty($filters)) {
            $query->filter($filters);
        }

        // 排序白名單檢查
        $allowedSortColumns = ['id', 'title', 'release_year', 'season_number', 'is_active', 'created_at', 'updated_at'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'updated_at';
        }

        return $query->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($item) => $this->formatListItem($item));
    }

    /**
     * 格式化列表項目（可在子類別覆寫）
     */
    protected function formatListItem($item)
    {
        $contentType = $this->getContentType();

        // 計算集數
        $episodeTable = $contentType === 'drama' ? 'drama_episodes' : 'program_episodes';
        $idField = $this->getVideoIdField();
        $episodeCount = DB::table($episodeTable)
            ->where($idField, $item->id)
            ->count();

        // 計算收藏人數
        $collectionCount = DB::table('user_collections')
            ->where('content_type', $contentType)
            ->where('content_id', $item->id)
            ->count();

        // 計算總觀看次數（聚合所有集數的觀看數）
        $totalViews = DB::table('view_statistics')
            ->where('content_type', $contentType)
            ->where('content_id', $item->id)
            ->sum('total_views');

        return [
            'id' => $item->id,
            'title_zh' => $item->getTranslation('title', 'zh_TW'),
            'title_en' => $item->getTranslation('title', 'en'),
            'category_name' => $item->category?->getTranslation('name', 'zh_TW'),
            'subcategory_name' => $item->subcategory?->getTranslation('name', 'zh_TW'),
            'category_subcategory' => $item->category?->getTranslation('name', 'zh_TW') .
                ($item->subcategory ? ' - ' . $item->subcategory->getTranslation('name', 'zh_TW') : ''),
            'release_year' => $item->release_year,
            'season_number' => $item->season_number,
            'episodes_count' => $episodeCount,
            'collection_count' => $collectionCount,      // 收藏人數
            'total_views' => (int) $totalViews,          // 總觀看次數
            'published_date' => $item->published_date?->toDateString(),
            'is_active' => (bool) $item->is_active,
            'updated_by_name' => $item->updatedBy?->name,
            'created_at' => $item->created_at->toDateTimeString(),
            'updated_at' => $item->updated_at->toDateTimeString(),
            'poster_desktop' => $item->posterDesktop?->url,
            'poster_mobile' => $item->posterMobile?->url,
        ];
    }

    /**
     * 刪除內容（含圖片和影片）
     */
    /**
     * 取得前台內容詳細資料（影片列表頁面用）
     *
     * @param int $contentId
     * @return array|null
     */
    public function getContentDetailForFrontend($contentId)
    {
        $contentType = $this->getContentType();
        $content = $this->model->newQuery()
            ->with([
                'posterDesktop',
                'posterMobile',
                'bannerDesktop',
                'bannerMobile',
                'category',
                'episodes' => function($query) {
                    $query->with('thumbnail')->orderBy('season')->orderBy('seq');
                }
            ])
            ->where('is_active', 1)
            ->whereNotNull('published_date')
            ->where('published_date', '<=', now())
            ->find($contentId);

        if (!$content) {
            return null;
        }

        // 將集數按季分組
        $episodesBySeason = collect();
        $content->episodes->groupBy('season')->each(function ($episodes, $season) use ($episodesBySeason, $content) {
            $episodesBySeason->put($season, $episodes->map(function ($episode) use ($content) {
                return [
                    'id' => $episode->id,
                    'title' => $episode->getTranslation('description', app()->getLocale()),
                    'season' => $episode->season,
                    'seq' => $episode->seq,
                    'duration' => $episode->getTranslation('duration_text', app()->getLocale()),
                    'thumbnail' => $episode->thumbnail
                        ? asset('storage/' . $episode->thumbnail->path)
                        : ($content->posterDesktop ? asset('storage/' . $content->posterDesktop->path) : ''),
                ];
            }));
        });

        // 季數資訊
        $seasonInfo = [];
        for ($i = 1; $i <= $content->season_number; $i++) {
            $seasonInfo[] = [
                'season' => $i,
                'episode_count' => $episodesBySeason->get($i, collect())->count()
            ];
        }

        return [
            $contentType => [
                'id' => $content->id,
                'title' => $content->getTranslation('title', app()->getLocale()),
                'description' => $content->getTranslation('description', app()->getLocale()),
                'cast' => $content->getTranslation('cast', app()->getLocale()),
                'crew' => $content->getTranslation('crew', app()->getLocale()),
                'tags' => $this->formatTagsArray($content->getTranslation('tags', app()->getLocale())),
                'other_info' => $content->getTranslation('other_info', app()->getLocale()),
                'banner_desktop' => $content->bannerDesktop ? $this->resolveImageUrl($content->bannerDesktop->path) : '',
                'banner_mobile' => $content->bannerMobile ? $this->resolveImageUrl($content->bannerMobile->path) : '',
                'poster_desktop' => $content->posterDesktop ? $this->resolveImageUrl($content->posterDesktop->path) : '',
                'poster_mobile' => $content->posterMobile ? $this->resolveImageUrl($content->posterMobile->path) : '',
                'season_number' => $content->season_number,
                'release_year' => $content->release_year,
                'category' => $content->category ? [
                    'id' => $content->category->id,
                    'name' => $content->category->getTranslation('name', app()->getLocale())
                ] : null,
            ],
            'episodes' => $episodesBySeason,
            'seasonInfo' => $seasonInfo
        ];
    }

    /**
     * 取得內容和指定集數資料（影片播放頁面用）
     *
     * @param int $contentId
     * @param int $episodeId
     * @return array|null
     */
    public function getContentWithEpisode($contentId, $episodeId)
    {
        $contentType = $this->getContentType();
        $content = $this->model->newQuery()
            ->with([
                'posterDesktop',
                'posterMobile',
                'bannerDesktop',
                'bannerMobile',
                'category',
                'episodes' => fn($q) => $q->with('thumbnail')->orderBy('season')->orderBy('seq')
            ])
            ->where('is_active', 1)
            ->whereNotNull('published_date')
            ->where('published_date', '<=', now())
            ->find($contentId);

        if (!$content) {
            return null;
        }

        // 找出當前集
        $currentEpisode = $content->episodes->firstWhere('id', $episodeId);
        if (!$currentEpisode) {
            return null;
        }

        // 解析影片 URL
        $videoUrl = null;
        $videoEmbedUrl = null;
        $videoFilePath = null;
        
        if ($currentEpisode->video_type === 'youtube') {
            $videoUrl = $currentEpisode->video_url;
            $videoEmbedUrl = \App\Helpers\YouTubeHelper::convertToEmbedUrl($currentEpisode->video_url);
        } elseif ($currentEpisode->video_type === 'upload') {
            $videoFilePath = $currentEpisode->video_file_path;
            $videoUrl = $currentEpisode->video_file_path
                ? asset('storage/' . $currentEpisode->video_file_path)
                : null;
        }

        // 找出上一集和下一集
        $currentIndex = $content->episodes->search(fn($e) => $e->id === $episodeId);
        $prevEpisode = $currentIndex > 0 ? $content->episodes[$currentIndex - 1] : null;
        $nextEpisode = $currentIndex < $content->episodes->count() - 1 ? $content->episodes[$currentIndex + 1] : null;

        return [
            $contentType => [
                'id' => $content->id,
                'title' => $content->getTranslation('title', app()->getLocale()),
                'description' => $content->getTranslation('description', app()->getLocale()),
                'cast' => $content->getTranslation('cast', app()->getLocale()),
                'crew' => $content->getTranslation('crew', app()->getLocale()),
                'tags' => $this->formatTagsArray($content->getTranslation('tags', app()->getLocale())),
                'poster_desktop' => $content->posterDesktop ? $this->resolveImageUrl($content->posterDesktop->path) : '',
                'poster_mobile' => $content->posterMobile ? $this->resolveImageUrl($content->posterMobile->path) : '',
                'banner_desktop' => $content->bannerDesktop ? $this->resolveImageUrl($content->bannerDesktop->path) : '',
                'banner_mobile' => $content->bannerMobile ? $this->resolveImageUrl($content->bannerMobile->path) : '',
                'season_number' => $content->season_number,
                'release_year' => $content->release_year,
                'published_date' => $content->published_date,
                'created_at' => $content->created_at,
                'updated_at' => $content->updated_at,
                'category' => $content->category ? [
                    'id' => $content->category->id,
                    'name' => $content->category->getTranslation('name', app()->getLocale())
                ] : null,
            ],
            'currentEpisode' => [
                'id' => $currentEpisode->id,
                'title' => $currentEpisode->getTranslation('description', app()->getLocale()),
                'description' => $currentEpisode->getTranslation('description', app()->getLocale()),
                'duration_text' => $currentEpisode->getTranslation('duration_text', app()->getLocale()),
                'season' => $currentEpisode->season,
                'seq' => $currentEpisode->seq,
                'video_url' => $videoUrl,
                'video_embed_url' => $videoEmbedUrl,
                'video_file_path' => $videoFilePath,
                'video_type' => $currentEpisode->video_type,
                'thumbnail' => $currentEpisode->thumbnail ? $this->resolveImageUrl($currentEpisode->thumbnail->path) : null,
                'created_at' => $currentEpisode->created_at,
                'updated_at' => $currentEpisode->updated_at,
                'published_date' => $currentEpisode->published_date ?? $currentEpisode->created_at,
            ],
            'episodes' => $content->episodes->groupBy('season'),
            'prevEpisode' => $prevEpisode,
            'nextEpisode' => $nextEpisode,
        ];
    }

    /**
     * 取得推薦內容
     * 邏輯：同子分類內容，按上架時間排序取前幾則
     *
     * @param int $contentId 當前內容ID
     * @param int $limit 推薦數量
     * @return array
     */
    public function getRecommendations($contentId, $limit = 4)
    {
        $locale = app()->getLocale();
        
        // 先取得當前內容的子分類ID
        $currentContent = $this->model->find($contentId);
        if (!$currentContent || !$currentContent->subcategory_id) {
            return [];
        }

        $recommendations = $this->model->newQuery()
            ->with(['posterDesktop', 'posterMobile'])
            ->where('subcategory_id', $currentContent->subcategory_id)
            ->where('id', '!=', $contentId) // 排除當前內容
            ->where('is_active', 1)
            ->whereNotNull('published_date')
            ->where('published_date', '<=', now())
            ->orderBy('published_date', 'desc') // 按上架時間降序
            ->take($limit)
            ->get();

        // 格式化推薦資料
        return $recommendations->map(function ($content) use ($locale) {
            return [
                'id' => $content->id,
                'title' => $content->getTranslation('title', $locale),
                'description' => \Str::limit($content->getTranslation('description', $locale), 100),
                'poster_desktop' => $content->posterDesktop ? '/' . $content->posterDesktop->path : '',
                'poster_mobile' => $content->posterMobile ? '/' . $content->posterMobile->path : '',
                'release_year' => $content->release_year,
                'season_number' => $content->season_number,
            ];
        })->toArray();
    }

    /**
     * 取得首頁內容（觀看數優先，無觀看數按上架日期倒序）
     * 
     * @param int $limit 限制數量
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForHomePage(int $limit = 10)
    {
        $locale = app()->getLocale();
        $contentType = $this->getContentType();
        $tableName = $this->model->getTable();
        
        // 使用子查詢從 view_statistics 表聚合所有集數的觀看數
        // 對於影音和節目，需要聚合該內容所有集數的觀看數
        $viewStatisticsSubquery = \DB::table('view_statistics')
            ->select('content_id', \DB::raw('SUM(total_views) as view_count'))
            ->where('content_type', $contentType)
            ->whereNotNull('episode_id')  // 只計算有集數的觀看記錄
            ->groupBy('content_id');
        
        return $this->model->newQuery()
            ->with(['posterDesktop', 'posterMobile'])
            ->leftJoinSub($viewStatisticsSubquery, 'views', function($join) use ($tableName) {
                $join->on($tableName . '.id', '=', 'views.content_id');
            })
            ->where('is_active', 1)
            ->whereNotNull('published_date')
            ->where('published_date', '<=', now())
            ->orderByDesc('views.view_count')
            ->orderByDesc($tableName . '.published_date')
            ->limit($limit)
            ->select([
                $tableName . '.*',
                \DB::raw('COALESCE(views.view_count, 0) as total_views')
            ])
            ->get()
            ->map(function ($item) use ($locale) {
                return [
                    'id' => $item->id,
                    'title' => $item->getTranslation('title', $locale),
                    'description' => $item->getTranslation('description', $locale),
                    'published_date' => $item->published_date,
                    'total_views' => $item->total_views ?? 0,
                    'poster_desktop' => $item->posterDesktop ? asset($this->resolveImageUrl($item->posterDesktop->path)) : null,
                    'poster_mobile' => $item->posterMobile ? asset($this->resolveImageUrl($item->posterMobile->path)) : null,
                ];
            });
    }

    public function delete($id)
    {
        DB::beginTransaction();
        
        try {
            $content = $this->find($id);
            
            if (!$content) {
                throw new \Exception('資料不存在');
            }

            // 刪除關聯圖片（遍歷刪除每一張圖片）
            foreach ($content->images as $image) {
                $this->imgRepository->deleteImgFile($image);
                $this->imgRepository->deleteImg($image);
            }

            // 刪除關聯影片（包含影片縮圖）
            foreach ($content->episodes as $episode) {
                // 刪除集數的縮圖
                if ($episode->thumbnail) {
                    $this->imgRepository->deleteImgFile($episode->thumbnail);
                    $this->imgRepository->deleteImg($episode->thumbnail);
                }
            }
            $content->episodes()->delete();

            // 刪除主題關聯
            $content->themes()->detach();

            // 刪除主資料
            $content->delete();

            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * 取得編輯表單資料（共用方法）
     */
    public function getEditFormData($id)
    {
        $content = $this->model->newQuery()
            ->with([
                'category:id,name',
                'subcategory:id,name',
                'posterDesktop',
                'posterMobile',
                'bannerDesktop',
                'bannerMobile',
                'episodes' => function($query) {
                    $query->orderBy('season')->orderBy('seq');
                }
            ])
            ->find($id);

        if (!$content) {
            return null;
        }

        // 計算影片季數
        $videoSeasons = $content->episodes->pluck('season')->unique()->sort()->values()->toArray();

        return [
            'id' => $content->id,
            'title' => [
                'zh_TW' => $content->getTranslation('title', 'zh_TW'),
                'en' => $content->getTranslation('title', 'en'),
            ],
            'description' => [
                'zh_TW' => $content->getTranslation('description', 'zh_TW') ?? '',
                'en' => $content->getTranslation('description', 'en') ?? '',
            ],
            'cast' => [
                'zh_TW' => $content->getTranslation('cast', 'zh_TW') ?? '',
                'en' => $content->getTranslation('cast', 'en') ?? '',
            ],
            'crew' => [
                'zh_TW' => $content->getTranslation('crew', 'zh_TW') ?? '',
                'en' => $content->getTranslation('crew', 'en') ?? '',
            ],
            'tags' => [
                'zh_TW' => $this->formatTagsForFrontend($content->getTranslation('tags', 'zh_TW')),
                'en' => $this->formatTagsForFrontend($content->getTranslation('tags', 'en')),
            ],
            'other_info' => [
                'zh_TW' => $content->getTranslation('other_info', 'zh_TW') ?? '',
                'en' => $content->getTranslation('other_info', 'en') ?? '',
            ],
            'category_id' => $content->category_id,
            'subcategory_id' => $content->subcategory_id,
            'season_number' => $content->season_number,
            'release_year' => $content->release_year,
            'published_date' => $content->published_date?->toDateString(),
            'is_active' => $content->is_active,
            
            // 圖片資料
            'poster_desktop' => $content->posterDesktop ? '/' . $content->posterDesktop->path : '',
            'poster_mobile' => $content->posterMobile ? '/' . $content->posterMobile->path : '',
            'banner_desktop' => $content->bannerDesktop ? '/' . $content->bannerDesktop->path : '',
            'banner_mobile' => $content->bannerMobile ? '/' . $content->bannerMobile->path : '',
            
            // 影片季數資料
            'video_seasons' => $videoSeasons,
            
            // 時間資訊
            'created_at' => $content->created_at->toDateTimeString(),
            'updated_at' => $content->updated_at->toDateTimeString(),
        ];
    }
    
    /**
     * 取得所有內容（供下拉選單使用）
     */
    public function getAllForSelect()
    {
        return $this->model->newQuery()
            ->select('id', 'title', 'category_id', 'subcategory_id')
            ->where('is_active', 1)
            ->orderBy('title->zh_TW')
            ->get()
            ->map(function ($content) {
                return [
                    'id' => $content->id,
                    'name_zh_tw' => $content->getTranslation('title', 'zh_TW'),
                    'name_en' => $content->getTranslation('title', 'en'),
                    'category_id' => $content->category_id,
                    'subcategory_id' => $content->subcategory_id,
                ];
            });
    }

    /**
     * 前台篩選內容（影音/節目共用）
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredContent(array $filters = [], $perPage = 18)
    {
        $query = $this->model->newQuery()
            ->with(['posterDesktop', 'posterMobile', 'images'])  // 載入所有圖片（含縮圖）
            ->where('is_active', 1)
            ->whereNotNull('published_date')
            ->where('published_date', '<=', now());

        // 主分類篩選
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // 子分類篩選（多選）
        if (!empty($filters['subcategories']) && is_array($filters['subcategories'])) {
            $query->whereIn('subcategory_id', $filters['subcategories']);
        }

        // 年份篩選（多選）
        if (!empty($filters['years']) && is_array($filters['years'])) {
            $years = collect($filters['years']);

            // 處理 "2015 以前" 的特殊情況
            if ($years->contains('before_2015')) {
                $normalYears = $years->filter(function ($year) {
                    return $year !== 'before_2015';
                })->toArray();

                $query->where(function ($q) use ($normalYears) {
                    if (!empty($normalYears)) {
                        $q->whereIn('release_year', $normalYears);
                    }
                    $q->orWhere('release_year', '<', 2015);
                });
            } else {
                $query->whereIn('release_year', $filters['years']);
            }
        }

        // 排序（預設按發布日期降序）
        $query->orderBy('published_date', 'desc');

        return $query->paginate($perPage)->through(function ($content) {
            return [
                'id' => $content->id,
                'title' => $content->getTranslation('title', app()->getLocale()),
                'poster_desktop' => $this->resolvePosterImage($content, 'desktop'),
                'poster_mobile' => $this->resolvePosterImage($content, 'mobile'),
                'release_year' => $content->release_year,
                'season_number' => $content->season_number,
            ];
        });
    }

    /**
     * 解析海報圖片（優先載入縮圖）
     *
     * 參考新聞圖片載入邏輯：優先使用縮圖，降級使用原圖
     *
     * @param mixed $content 內容模型實例
     * @param string $type 圖片類型 ('desktop' 或 'mobile')
     * @return string 完整圖片 URL
     */
    private function resolvePosterImage($content, $type = 'desktop')
    {
        $imageType = $type === 'desktop' ? 'poster_desktop' : 'poster_mobile';
        $thumbnailType = $type === 'desktop' ? 'poster_desktop_thumbnail' : 'poster_mobile_thumbnail';

        // 1️⃣ 優先使用縮圖（效能最佳）
        if ($content->images) {
            $thumbnail = $content->images
                ->where('image_type', $thumbnailType)
                ->first();

            if ($thumbnail) {
                return $this->resolveImageUrl($thumbnail->path);
            }
        }

        // 2️⃣ 降級使用原圖
        $relation = $type === 'desktop' ? 'posterDesktop' : 'posterMobile';
        if ($content->{$relation}) {
            return $this->resolveImageUrl($content->{$relation}->path);
        }

        // 3️⃣ 無圖片時回傳空字串（前端會使用預設圖）
        return '';
    }
}