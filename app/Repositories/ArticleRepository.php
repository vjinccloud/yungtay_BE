<?php

namespace App\Repositories;

use App\Models\Article;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Traits\CommonTrait;
use App\Repositories\ImageRepository;

class ArticleRepository extends BaseRepository
{
    public function __construct(
        Article $article,
        private ImageRepository $imgRepository)
    {
        parent::__construct($article);
    }

    public function save(array $attributes = [], $id = null)
    {
        // 1. 先從 attributes 中拆出 slim 圖片資料
        $slimData = null;

        if (array_key_exists('slim', $attributes)) {
            if (!is_null($attributes['slim'])) {
                $slimData = $attributes['slim'];
            }

            unset($attributes['slim']); // 從主資料中移除，避免寫入到資料庫
        }

        // 2. 儲存主資料（此時會取得 ID）
        $article = parent::save($attributes, $id); // 必須先 save 起來

        // 3. 圖片處理（Slim 圖片上傳 - 啟用壓縮和縮圖）
        if ($slimData) {
            $this->processSlimImage($article, $slimData);
        }

        return $article;
    }

    /**
     * 處理 Slim 圖片（含壓縮和縮圖生成）
     *
     * @param Article $article 文章模型實例
     * @param string $slimData Slim 圖片資料
     * @return void
     */
    public function processSlimImage($article, $slimData)
    {
        $imageData = ['image_normal' => $slimData];

        $oldImage = $article->image ?? null;

        // 刪除舊的縮圖（如果存在）
        if ($oldImage) {
            $oldThumbnail = $article->images()
                ->where('image_type', 'image_thumbnail')
                ->first();
            if ($oldThumbnail) {
                $this->imgRepository->deleteImgFile($oldThumbnail);
                $this->imgRepository->deleteImg($oldThumbnail);
            }
        }

        $this->imgRepository->saveSlimFile(
            $imageData,
            $article,
            $oldImage,
            'articles',          // 路徑
            true,               // 啟用壓縮 (JPEG 92% 品質)
            true                // 啟用縮圖生成
        );
    }

    public function paginate($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->model->orderBy($sortColumn, $sortDirection)
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($article) => [
                'id'             => $article->id,
                // 取出各語系的標題
                'title_zh'       => $article->getTranslation('title', 'zh_TW'),
                'title_en'       => $article->getTranslation('title', 'en'),
                'author_zh'      => $article->getTranslation('author', 'zh_TW'),
                'author_en'      => $article->getTranslation('author', 'en'),
                'location_zh'    => $article->getTranslation('location', 'zh_TW'),
                'location_en'    => $article->getTranslation('location', 'en'),
                'tags_zh'        => $article->getTranslation('tags', 'zh_TW'),
                'tags_en'        => $article->getTranslation('tags', 'en'),
                'category_name'  => $article->category?->getTranslation('name', 'zh_TW') ?? '',
                'source_link'    => $article->source_link, // RSS 來源連結
                'is_active'      => (bool) $article->is_active,
                'publish_date'   => $article->publish_date->toDateString(),
                'created_at'     => $article->created_at->toDateTimeString(),
                'updated_at'     => $article->updated_at->toDateTimeString(),
            ]);
    }

    /**
     * 取得首頁「三天熱門新聞」（三天內發布的新聞，依觀看數排序，不足則用發布時間補齊）
     *
     * @param int $limit 限制數量（預設 9）
     * @param int $days 天數限制（預設 3 天）
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHotNewsForHomePage(int $limit = 9, int $days = 3)
    {
        $threeDaysAgo = now()->subDays($days)->format('Y-m-d');

        // 第一步：取得三天內發布的新聞中，觀看數最高的
        $hotNews = $this->model->newQuery()
            ->with(['image', 'images', 'category'])
            ->join('view_statistics', function($join) {
                $join->on('articles.id', '=', 'view_statistics.content_id')
                     ->where('view_statistics.content_type', '=', 'article')
                     ->whereNull('view_statistics.episode_id');
            })
            ->where('articles.is_active', 1)
            ->whereNotNull('articles.publish_date')
            ->where('articles.publish_date', '>=', $threeDaysAgo)
            ->where('articles.publish_date', '<=', now())
            ->orderByDesc('view_statistics.total_views')
            ->orderByDesc('articles.publish_date')
            ->limit($limit)
            ->select([
                'articles.*',
                'view_statistics.total_views'
            ])
            ->get();

        // 如果熱門新聞數量已達限制，直接返回
        if ($hotNews->count() >= $limit) {
            return $hotNews->map(fn($item) => $this->formatArticleForFrontend($item, false, false));
        }

        // 第二步：不足 9 則，用最新發布的新聞補齊（排除已經在熱門新聞中的）
        $excludeIds = $hotNews->pluck('id')->toArray();
        $remaining = $limit - $hotNews->count();

        $latestNews = $this->model->newQuery()
            ->with(['image', 'images', 'category'])
            ->where('is_active', 1)
            ->whereNotNull('publish_date')
            ->where('publish_date', '<=', now())
            ->whereNotIn('id', $excludeIds)
            ->orderByDesc('publish_date')
            ->limit($remaining)
            ->select(['articles.*'])
            ->get();

        // 合併熱門新聞和最新新聞
        $combined = $hotNews->concat($latestNews);

        return $combined->map(fn($item) => $this->formatArticleForFrontend($item, false, false));
    }

    /**
     * 前台取得新聞列表
     *
     * @param int $perPage 每頁筆數
     * @param int|null $categoryId 分類ID
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getFrontendList($perPage = 20, $categoryId = null)
    {
        $query = $this->model->frontend()
            ->with(['image', 'images', 'category']); // 載入圖片關聯

        // 分類篩選
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('publish_date', 'desc')
            ->paginate($perPage)
            ->through(fn($article) => $this->formatArticleForFrontend($article));
    }

    /**
     * 取得文章詳情（含相關資料）
     */
    public function getDetail($id)
    {
        $article = $this->model->with([
            'category',
            'image',
            'created_user',
            'updated_user'
        ])->findOrFail($id);

        // 格式化資料，參考 paginate 方法的做法
        return [
            'id' => $article->id,
            'title' => [
                'zh_TW' => $article->getTranslation('title', 'zh_TW'),
                'en' => $article->getTranslation('title', 'en'),
            ],
            'content' => [
                'zh_TW' => $article->getTranslation('content', 'zh_TW'),
                'en' => $article->getTranslation('content', 'en'),
            ],
            'author' => [
                'zh_TW' => $article->getTranslation('author', 'zh_TW'),
                'en' => $article->getTranslation('author', 'en'),
            ],
            'location' => [
                'zh_TW' => $article->getTranslation('location', 'zh_TW'),
                'en' => $article->getTranslation('location', 'en'),
            ],
            'tags' => [
                'zh_TW' => $article->getTranslation('tags', 'zh_TW'),
                'en' => $article->getTranslation('tags', 'en'),
            ],
            'category_id' => $article->category_id,
            'is_active' => (bool) $article->is_active,
            'publish_date' => $article->publish_date?->toDateString(),
            'img' => $article->image ? '/'.$article->image->path : null
        ];
    }

    /**
     * 前台取得單一新聞
     *
     * @param int $id
     * @return array|null
     */
    public function getFrontendDetail($id)
    {
        $article = $this->model->frontend()
            ->with(['image', 'images', 'category'])
            ->find($id);

        if (!$article) {
            return null;
        }

        return $this->formatArticleForFrontend($article, true, false);
    }

    /**
     * 取得文章集合（前台用）
     * 邏輯：優先取今天發布的新聞（隨機），不足則用最新發布日期的新聞補齊（不隨機）
     *
     * @param int $limit 限制筆數
     * @param int|null $categoryId 分類ID
     * @param int|null $excludeId 排除的文章ID
     */
    public function getFrontendArticles($limit = 4, $categoryId = null, $excludeId = null)
    {
        $today = now()->startOfDay()->format('Y-m-d');

        // 第一步：查詢今天發布的新聞（隨機排序）
        $query = $this->model->frontend()
            ->with(['image', 'images', 'category'])
            ->whereDate('publish_date', $today);

        // 分類篩選
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // 排除指定文章
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // 今天的新聞（隨機排序）
        $todayArticles = $query->inRandomOrder()->limit($limit)->get();

        // 如果今天的資料已經足夠，直接返回
        if ($todayArticles->count() >= $limit) {
            return $todayArticles->map(fn($article) => $this->formatArticleForFrontend($article));
        }

        // 第二步：今天不足，用最新發布的新聞補齊（不隨機）
        $excludeIds = $todayArticles->pluck('id')->toArray();
        $remaining = $limit - $todayArticles->count();

        $latestArticles = $this->model->frontend()
            ->with(['image', 'images', 'category'])
            ->whereDate('publish_date', '<', $today)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->whereNotIn('id', $excludeIds)
            ->orderByDesc('publish_date')
            ->orderByDesc('id')
            ->limit($remaining)
            ->get();

        // 合併兩組資料
        $combined = $todayArticles->concat($latestArticles);

        return $combined->map(fn($article) => $this->formatArticleForFrontend($article));
    }



    /**
     * RSS 相關查詢方法
     */

    /**
     * 根據來源提供者查詢文章
     *
     * @param string $provider 來源提供者（如 'cna'）
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBySourceProvider(string $provider)
    {
        return $this->model->fromRss($provider);
    }

    /**
     * 根據 RSS GUID Hash 查找文章
     *
     * @param string $provider 來源提供者
     * @param string $guidHash GUID Hash
     * @return \App\Models\Article|null
     */
    public function findByGuidHash(string $provider, string $guidHash): ?Article
    {
        return $this->model->byRssGuid($provider, $guidHash)->first();
    }

    /**
     * RSS 文章的 upsert 操作（更新或建立）
     *
     * @param array $data 文章資料
     * @param string $provider 來源提供者
     * @param string $guidHash GUID Hash
     * @return \App\Models\Article
     */
    public function upsertByRssGuid(array $data, string $provider, string $guidHash): Article
    {
        return $this->model->updateOrCreate(
            [
                'source_provider' => $provider,
                'source_guid_hash' => $guidHash,
            ],
            $data
        );
    }

    /**
     * 取得需要更新的 RSS 文章
     * 根據 source_modified_at 或 source_comments_count 判斷
     *
     * @param string $provider 來源提供者
     * @param \Carbon\Carbon $since 從什麼時間開始
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStaleRssArticles(string $provider, $since = null)
    {
        $query = $this->model->fromRss($provider);

        if ($since) {
            $query->where('source_modified_at', '>=', $since);
        }

        return $query->orderBy('source_modified_at', 'desc')->get();
    }

    /**
     * 解析文章圖片路徑（優先使用縮圖）
     *
     * @param \App\Models\Article $article
     * @return string|null
     */
    private function resolveArticleImage($article): ?string
    {
        // 優先使用縮圖
        $thumbnail = $article->images
            ->where('image_type', 'image_thumbnail')
            ->first();

        if ($thumbnail) {
            return asset($this->resolveImageUrl($thumbnail->path));
        }

        if ($article->image) {
            return asset($this->resolveImageUrl($article->image->path));
        }

        return null;
    }

    /**
     * 解析文章 JSON-LD 圖片路徑（優先使用大圖）
     *
     * @param \App\Models\Article $article
     * @return string|null
     */
    private function resolveArticleJsonLdImage($article): ?string
    {
        // 優先使用大圖（原圖）
        if ($article->image) {
            return asset($this->resolveImageUrl($article->image->path));
        }

        // 沒有大圖就用縮圖
        $thumbnail = $article->images
            ->where('image_type', 'image_thumbnail')
            ->first();

        if ($thumbnail) {
            return asset($this->resolveImageUrl($thumbnail->path));
        }

        return null;
    }

    /**
     * 統一格式化前台文章資料
     *
     * @param \App\Models\Article $article
     * @param bool $includeContent 是否包含完整內容
     * @param bool $includeExcerpt 是否包含摘要
     * @return array
     */
    private function formatArticleForFrontend($article, bool $includeContent = false, bool $includeExcerpt = true): array
    {
        $locale = app()->getLocale();
        
        $data = [
            'id' => $article->id,
            'title' => $article->getTranslation('title', $locale),
            'author' => $article->getTranslation('author', $locale),
            'location' => $article->getTranslation('location', $locale),
            'tags' => $article->getTranslation('tags', $locale),
            'publish_date' => $article->publish_date->format('Y.m.d'),
            'category_name' => $article->category?->getTranslation('name', $locale),
            'category_id' => $article->category_id,
            'image' => $this->resolveArticleImage($article),  // 預設圖片（縮圖優先）
            'image_original' => $this->resolveArticleJsonLdImage($article),  // 正式大圖（重用現有方法）
            'image_thumbnail' => $this->resolveArticleImage($article),  // 縮圖（重用現有方法）
            'jsonld_image' => $this->resolveArticleJsonLdImage($article),
            'jsonld_publish_date' => $article->publish_date,
            'created_at' => $article->created_at,
            'updated_at' => $article->updated_at,
            'total_views' => $article->total_views ?? 0,
        ];
        
        // 根據需求包含內容或摘要
        if ($includeContent) {
            $data['content'] = $article->getTranslation('content', $locale);
        } elseif ($includeExcerpt) {
            $data['excerpt'] = mb_substr(strip_tags($article->getTranslation('content', $locale)), 0, 100) . '...';
        }
        
        // 如果有建立時間，加入建立時間
        if (isset($article->created_at)) {
            $data['created_at'] = $article->created_at->toDateTimeString();
        }
        
        return $data;
    }
}
