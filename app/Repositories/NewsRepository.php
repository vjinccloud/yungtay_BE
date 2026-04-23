<?php

namespace App\Repositories;

use App\Models\News;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Traits\CommonTrait;
use App\Repositories\ImageRepository;

class NewsRepository extends BaseRepository
{
    use CommonTrait;

    public function __construct(
        News $news,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($news);
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
        $news = parent::save($attributes, $id); // 必須先 save 起來

        // 3. 圖片處理（Slim 圖片上傳）
        if ($slimData) {
            $imageData = ['image' => $slimData];

            $oldImage = $news->image ?? null;

            $this->imgRepository->saveSlimFile(
                $imageData,
                $news,
                $oldImage,
                'news' // 可根據實際需要指定路徑
            );
        }

        return $news;
    }

    /**
     * 取得最新焦點（按刊登時間最新）
     * 
     * @param int $limit 限制數量
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLatestFocus(int $limit = 6)
    {
        $locale = app()->getLocale();

        return $this->model->newQuery()
            ->with(['image'])
            ->where('is_active', 1)
            ->whereNotNull('published_date')
            ->where('published_date', '<=', now())
            ->orderByDesc('published_date')
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($locale) {
                return [
                    'id' => $item->id,
                    'title' => $item->getTranslation('title', $locale),
                    'content' => $item->getTranslation('content', $locale),
                    'published_date' => $item->published_date,
                    'image' => $item->image ? asset($this->resolveImageUrl($item->image->path)) : null,
                ];
            });
    }

    /**
     * 排序欄位映射（前端欄位名 => 資料庫欄位名）
     */
    protected function getSortColumnMap(): array
    {
        return [
            'title_zh' => 'title',
            'title_en' => 'title',
            'category_name' => 'category_id',
        ];
    }

    public function paginate($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        // 轉換排序欄位名稱
        $columnMap = $this->getSortColumnMap();
        $sortColumn = $columnMap[$sortColumn] ?? $sortColumn;

        return $this->model->orderBy($sortColumn, $sortDirection)
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($news) => [
                'id'             => $news->id,
                'category_id'    => $news->category_id,
                'category_name'  => $news->category?->getTranslation('name', 'zh_TW'),
                // 取出各語系的標題
                'title_zh'       => $news->getTranslation('title', 'zh_TW'),
                'title_en'       => $news->getTranslation('title', 'en'),
                'is_active'      => (bool) $news->is_active,
                'is_homepage_featured' => (bool) $news->is_homepage_featured,
                'is_pinned'      => (bool) $news->is_pinned,
                'published_date'   => $news->published_date->toDateString(),
                'created_at'     => $news->created_at->toDateTimeString(),
                'updated_at'     => $news->updated_at->toDateTimeString(),
            ]);
    }

    /**
     * 計算首頁曝光文章數量
     */
    public function countHomepageFeatured(): int
    {
        return $this->model->where('is_homepage_featured', true)->count();
    }

    /**
     * 計算置頂文章數量
     */
    public function countPinned(): int
    {
        return $this->model->where('is_pinned', true)->count();
    }


    /**
     * 前台取得最新消息列表
     *
     * @param int $perPage 每頁筆數
     * @param string|null $search 搜尋關鍵字
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getFrontendList($perPage = 6, $search = null)
    {
        $query = $this->model
            ->with('image')
            ->active()
            ->where('published_date', '<=', now());

        // 搜尋功能
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.zh_TW')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.zh_TW')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.en')) LIKE ?", ["%{$search}%"]);
            });
        }

        return $query->orderBy('published_date', 'desc')
            ->paginate($perPage)
            ->through(function ($news) {
                return [
                    'id' => $news->id,
                    'title' => $news->getTranslation('title', app()->getLocale()),
                    'content' => $news->getTranslation('content', app()->getLocale()),
                    'image' => $news->image ?  asset($this->resolveImageUrl($news->image->path)) : null,
                    'publish_date' => $news->published_date->format('Y.m.d'),
                    'published_date' => $news->published_date,
                    'created_at' => $news->created_at,
                    'updated_at' => $news->updated_at,
                ];
            });
    }

    /**
     * 前台取得單一最新消息
     *
     * @param int $id
     * @return array|null
     */
    public function getFrontendDetail($id)
    {
        $news = $this->model
            ->active()
            ->where('published_date', '<=', now())
            ->find($id);

        if (!$news) {
            return null;
        }

        return [
            'id' => $news->id,
            'title' => $news->getTranslation('title', app()->getLocale()),
            'content' => $news->getTranslation('content', app()->getLocale()),
            'publish_date' => $news->published_date->format('Y.m.d'),
            'published_date' => $news->published_date,
            'image' => $news->image ? $news->image->path : null,
            'created_at' => $news->created_at,
            'updated_at' => $news->updated_at,
        ];
    }

    /**
     * 取得新聞詳情（含相關資料）
     */
    public function getDetail($id)
    {
        $news = $this->model->with([
            'image',
            'creator',
            'updater',
            'category'
        ])->findOrFail($id);

        // 格式化資料，參考 paginate 方法的做法
        return [
            'id' => $news->id,
            'category_id' => $news->category_id,
            'title' => [
                'zh_TW' => $news->getTranslation('title', 'zh_TW'),
                'en' => $news->getTranslation('title', 'en'),
            ],
            'content' => [
                'zh_TW' => $news->getTranslation('content', 'zh_TW'),
                'en' => $news->getTranslation('content', 'en'),
            ],
            'description' => $news->description,
            'tags' => $news->tags,
            'is_active' => (bool) $news->is_active,
            'published_date' => $news->published_date?->toDateString(),
            'img' => $news->image ? '/' . $news->image->path : null
        ];
    }
}
