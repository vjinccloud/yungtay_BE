<?php

namespace App\Repositories;

use App\Models\Radio;
use App\Models\Category;
use App\Repositories\BaseRepository;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Storage;

class RadioRepository extends BaseRepository
{
    public function __construct(
        Radio $radio,
        private ImageRepository $imgRepository)
    {
        parent::__construct($radio);
    }

    /**
     * 儲存廣播資料（含圖片與音訊處理）
     */
    public function save(array $attributes = [], $id = null)
    {
        // 1. 先從 attributes 中拆出 slim 圖片資料
        $slimData = null;
        $bannerDesktopData = null;
        $bannerMobileData = null;

        // 封面圖片
        if (array_key_exists('slim', $attributes)) {
            if (!is_null($attributes['slim'])) {
                $slimData = $attributes['slim'];
            }
            unset($attributes['slim']); // 從主資料中移除，避免寫入到資料庫
        }

        if (array_key_exists('slimCleared', $attributes)) {
            unset($attributes['slimCleared']);
        }

        // Banner 桌機版圖片
        if (array_key_exists('banner_desktop', $attributes)) {
            if (!is_null($attributes['banner_desktop'])) {
                $bannerDesktopData = $attributes['banner_desktop'];
            }
            unset($attributes['banner_desktop']);
        }

        if (array_key_exists('slimClearedBannerDesktop', $attributes)) {
            unset($attributes['slimClearedBannerDesktop']);
        }

        // Banner 手機版圖片
        if (array_key_exists('banner_mobile', $attributes)) {
            if (!is_null($attributes['banner_mobile'])) {
                $bannerMobileData = $attributes['banner_mobile'];
            }
            unset($attributes['banner_mobile']);
        }

        if (array_key_exists('slimClearedBannerMobile', $attributes)) {
            unset($attributes['slimClearedBannerMobile']);
        }

        // 2. 處理音訊檔案搬移（從暫存區到正式區）
        if (!empty($attributes['audio_url'])) {
            $oldRadio = $id ? $this->find($id) : null;
            $oldAudioPath = $oldRadio->audio_url ?? null;

            // 如果是暫存區的檔案，移動到正式區
            $newAudioPath = $this->moveUploadedAudioFile($attributes['audio_url']);

            // 刪除舊音訊檔案（如果有換檔）
            if ($oldRadio && $oldAudioPath && $oldAudioPath !== $newAudioPath) {
                Storage::disk('public')->delete($oldAudioPath);
            }

            $attributes['audio_url'] = $newAudioPath;
        }

        // 3. 儲存主資料（此時會取得 ID）
        $radio = parent::save($attributes, $id); // 必須先 save 起來

        // 4. 封面圖片處理（Slim 圖片上傳）
        if ($slimData) {
            $imageData = ['image' => $slimData];

            $oldImage = $radio->image ?? null;

            $this->imgRepository->saveSlimFile(
                $imageData,
                $radio,
                $oldImage,
                'radios' // 圖片儲存路徑
            );
        }

        // 5. Banner 桌機版圖片處理（不壓縮，保持原始尺寸 1915x798）
        if ($bannerDesktopData) {
            $imageData = ['banner_desktop' => $bannerDesktopData]; // key 即為 image_type
            $oldImage = $radio->bannerDesktop ?? null;

            $this->imgRepository->saveSlimFile(
                $imageData,
                $radio,
                $oldImage,
                'radios', // 圖片儲存路徑
                false     // 不壓縮，保持原始尺寸
            );
        }

        // 6. Banner 手機版圖片處理（不壓縮，保持原始尺寸 430x240）
        if ($bannerMobileData) {
            $imageData = ['banner_mobile' => $bannerMobileData]; // key 即為 image_type
            $oldImage = $radio->bannerMobile ?? null;

            $this->imgRepository->saveSlimFile(
                $imageData,
                $radio,
                $oldImage,
                'radios', // 圖片儲存路徑
                false     // 不壓縮，保持原始尺寸
            );
        }

        return $radio;
    }

    /**
     * 移動上傳的音訊檔案從暫存區到正式區
     * 參考 DramaEpisodeRepository::moveUploadedFile 的邏輯
     *
     * @param string $tempPath 暫存路徑
     * @return string 正式路徑
     */
    protected function moveUploadedAudioFile($tempPath)
    {
        // 如果不是暫存區的檔案，直接返回原路徑
        if (!str_contains($tempPath, 'tmp/audios/')) {
            return $tempPath;
        }

        // 檢查檔案是否存在
        if (!Storage::disk('public')->exists($tempPath)) {
            return $tempPath; // 檔案不存在，返回原路徑
        }

        // 生成正式路徑
        $filename = basename($tempPath);
        $finalPath = 'radios/audios/' . $filename;

        // 確保目錄存在
        Storage::disk('public')->makeDirectory('radios/audios');

        // 移動檔案
        Storage::disk('public')->move($tempPath, $finalPath);

        return $finalPath;
    }

    /**
     * 後台廣播列表（分頁）
     */
    public function paginate($perPage, $sortColumn = 'publish_date', $sortDirection = 'desc', $filters = [])
    {
        return $this->model->with(['category', 'subcategory'])
            ->leftJoin('view_statistics', function ($join) {
                $join->on('radios.id', '=', 'view_statistics.content_id')
                    ->where('view_statistics.content_type', '=', 'radio')
                    ->whereNull('view_statistics.episode_id');
            })
            ->select('radios.*', 'view_statistics.total_views')
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('radios.id', 'desc')
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($radio) => [
                'id'               => $radio->id,
                // 取出各語系的標題
                'title_zh'         => $radio->getTranslation('title', 'zh_TW'),
                'title_en'         => $radio->getTranslation('title', 'en'),
                'category_name'    => $radio->category?->getTranslation('name', 'zh_TW') ?? '',
                'subcategory_name' => $radio->subcategory?->getTranslation('name', 'zh_TW') ?? '',
                'year'             => $radio->year,
                'audio_url'        => $radio->audio_url,
                'is_active'        => (bool) $radio->is_active,
                'publish_date'     => $radio->publish_date?->toDateString(),
                'created_at'       => $radio->created_at->toDateTimeString(),
                'updated_at'       => $radio->updated_at->toDateTimeString(),
                'total_views'      => $radio->total_views ?? 0,
            ]);
    }

    /**
     * 取得前台廣播列表（分頁 + 格式化）
     */
    public function getFrontendList($filters = [], $perPage = 20)
    {
        $query = $this->model->newQuery()
            ->with(['category', 'image'])
            ->active()
            ->where('publish_date', '<=', now())
            ->filter($filters);  // 使用 Model 的 scopeFilter

        // 按發布日期倒序，取得分頁結果並格式化
        return $query->orderBy('publish_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->paginate($perPage)
                    ->through(function ($radio) {
                        return [
                            'id' => $radio->id,
                            'title' => $radio->getTranslation('title', app()->getLocale()),
                            'media_name' => $radio->getTranslation('media_name', app()->getLocale()),
                            'category' => $radio->category ? $radio->category->getTranslation('name', app()->getLocale()) : null,
                            'audio_url' => $radio->audio_url,
                            'image' => $radio->image ?  asset($this->resolveImageUrl($radio->image->path)) : null,
                            'publish_date' => $radio->publish_date ? $radio->publish_date->format('Y.m.d') : null,
                        ];
                    });
    }

    /**
     * 取得廣播詳情（含相關資料）- 給編輯表單用
     */
    public function getDetail($id)
    {
        $radio = $this->model->with([
            'category',
            'subcategory',
            'image',
            'bannerDesktop',
            'bannerMobile',
            'created_user',
            'updated_user'
        ])->findOrFail($id);
        // 格式化資料，參考 paginate 方法的做法
        return [
            'id' => $radio->id,
            'title' => [
                'zh_TW' => $radio->getTranslation('title', 'zh_TW'),
                'en' => $radio->getTranslation('title', 'en'),
            ],
            'description' => [
                'zh_TW' => $radio->getTranslation('description', 'zh_TW'),
                'en' => $radio->getTranslation('description', 'en'),
            ],
            'media_name' => [
                'zh_TW' => $radio->getTranslation('media_name', 'zh_TW'),
                'en' => $radio->getTranslation('media_name', 'en'),
            ],
            'category_id' => $radio->category_id,
            'subcategory_id' => $radio->subcategory_id,
            'year' => $radio->year,
            'season' => $radio->season,
            'audio_url' => $radio->audio_url,
            'is_active' => (bool) $radio->is_active,
            'publish_date' => $radio->publish_date?->toDateString(),
            'img' => $radio->image ? asset($radio->image->path) : null,
            'banner_desktop' => $radio->bannerDesktop ? asset($radio->bannerDesktop->path) : null,
            'banner_mobile' => $radio->bannerMobile ? asset($radio->bannerMobile->path) : null
        ];
    }

    /**
     * 取得前台廣播詳情（根據語系處理好所有資料）
     */
    public function getFrontendDetail($id)
    {
        $radio = $this->model->with([
            'category',
            'image',
            'bannerDesktop',
            'bannerMobile',
            'episodes' => function ($query) {
                $query->where('is_active', true)
                      ->orderBy('season', 'asc')
                      ->orderBy('episode_number', 'asc');
            }
        ])
            ->active()
            ->where('publish_date', '<=', now())
            ->findOrFail($id);

        $locale = app()->getLocale();

        // 取得所有啟用的季數
        $seasons = $radio->episodes->pluck('season')->unique()->sort()->values()->toArray();

        // 計算總季數（最大季數）
        $totalSeasons = count($seasons) > 0 ? max($seasons) : 0;

        // 格式化集數資料，依季數分組
        $episodesBySeason = [];
        foreach ($radio->episodes as $episode) {
            $season = $episode->season;
            if (!isset($episodesBySeason[$season])) {
                $episodesBySeason[$season] = [];
            }
            $episodesBySeason[$season][] = [
                'id' => $episode->id,
                'episode_number' => $episode->episode_number,
                'duration_text' => $episode->getTranslation('duration_text', $locale),
                'description' => $episode->getTranslation('description', $locale),
                'audio_url' => $episode->audio_path ? asset('storage/' . $episode->audio_path) : null,
                'duration' => $episode->duration,
            ];
        }

        return [
            'id' => $radio->id,
            'title' => $radio->getTranslation('title', $locale),
            'description' => $radio->getTranslation('description', $locale),
            'media_name' => $radio->getTranslation('media_name', $locale),
            'category' => $radio->category ? $radio->category->getTranslation('name', $locale) : null,
            'category_id' => $radio->category_id,
            'year' => $radio->year,
            'total_seasons' => $totalSeasons,
            'seasons' => $seasons,
            'episodes_by_season' => $episodesBySeason,
            'image' => $radio->image ? $this->resolveImageUrl($radio->image->path) : null,
            'banner_desktop' => $radio->bannerDesktop ? asset($radio->bannerDesktop->path) : null,
            'banner_mobile' => $radio->bannerMobile ? asset($radio->bannerMobile->path) : null,
            'publish_date' => $radio->publish_date ? $radio->publish_date->format('Y.m.d') : null,
            'publish_date_raw' => $radio->publish_date,  // 保留原始 Carbon 物件供 JSON-LD 使用
            'created_at' => $radio->created_at,
            'updated_at' => $radio->updated_at
        ];
    }

    /**
     * 首頁取得廣播列表（隨機排序）
     *
     * @param int $limit 限制數量
     * @return \Illuminate\Support\Collection
     */
    public function getForHomePage(int $limit = 8)
    {
        $locale = app()->getLocale();

        return $this->model->with(['image'])
            ->active()
            ->where('publish_date', '<=', now())
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(function ($radio) use ($locale) {
                return [
                    'id' => $radio->id,
                    'title' => $radio->getTranslation('title', $locale),
                    'media_name' => $radio->getTranslation('media_name', $locale),
                    'audio_url' => $radio->audio_url,
                    'publish_date' => $radio->publish_date,
                    'image' => $radio->image ? asset($this->resolveImageUrl($radio->image->path)) : null,
                ];
            });
    }

    /**
     * 根據主分類 ID 取得子分類列表
     *
     * @param int $categoryId 主分類 ID
     * @return \Illuminate\Support\Collection
     */
    public function getSubcategoriesByCategoryId(int $categoryId)
    {
        return Category::where('parent_id', $categoryId)
            ->where('type', 'radio')
            ->where('status', true)
            ->ordered()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->getTranslation('name', 'zh_TW'),
                    'name_en' => $category->getTranslation('name', 'en'),
                ];
            });
    }

    /**
     * 取得所有廣播選項（供下拉選單使用）
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllForSelect()
    {
        return $this->model->newQuery()
            ->select('id', 'title', 'category_id')
            ->where('is_active', 1)
            ->orderBy('title->zh_TW')
            ->get()
            ->map(function ($radio) {
                return [
                    'id' => $radio->id,
                    'name_zh_tw' => $radio->getTranslation('title', 'zh_TW'),
                    'name_en' => $radio->getTranslation('title', 'en'),
                    'category_id' => $radio->category_id,
                ];
            });
    }

    /**
     * 前台篩選廣播（供 API 使用）
     *
     * 與影音/節目不同的地方：
     * - 廣播只有單張圖片（image），無 posterDesktop/posterMobile
     * - 廣播使用 year 欄位，非 release_year
     * - 廣播使用 publish_date 欄位，非 published_date
     *
     * @param array $filters 篩選條件（category_id, subcategories, years）
     * @param int $perPage 每頁數量
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredRadios(array $filters = [], $perPage = 18)
    {
        $query = $this->model->newQuery()
            ->with(['image'])
            ->active()
            ->where('publish_date', '<=', now())
            ->filter($filters);  // 使用 Model 的 scopeFilter

        // 排序（預設按發布日期降序）
        $query->orderBy('publish_date', 'desc')
              ->orderBy('id', 'desc');

        return $query->paginate($perPage)->through(function ($radio) {
            return [
                'id' => $radio->id,
                'title' => $radio->getTranslation('title', app()->getLocale()),
                'media_name' => $radio->getTranslation('media_name', app()->getLocale()),
                'image' => $radio->image ? $this->resolveImageUrl($radio->image->path) : null,
                'year' => $radio->year,
                'publish_date' => $radio->publish_date ? $radio->publish_date->format('Y.m.d') : null,
            ];
        });
    }

}
