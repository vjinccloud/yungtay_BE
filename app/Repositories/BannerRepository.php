<?php

namespace App\Repositories;

use App\Models\Banner;
use App\Repositories\BaseRepository;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Log;

class BannerRepository extends BaseRepository
{
    public function __construct(
        Banner $banner,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($banner);
    }

    /**
     * 儲存 Banner 資料（含圖片處理）
     * 參考 RadioRepository 的處理方式
     */
    public function save(array $attributes = [], $id = null)
    {
        // 0. 新增時自動設定排序值
        if (!$id && !isset($attributes['sort_order'])) {
            $attributes['sort_order'] = $this->getNextSortOrder();
        }
        
        // 1. 先從 attributes 中拆出 slim 圖片資料
        $slimDesktopData = null;
        $slimMobileData = null;

        if (array_key_exists('slimDesktop', $attributes)) {
            if (!is_null($attributes['slimDesktop'])) {
                $slimDesktopData = $attributes['slimDesktop'];
            }
            unset($attributes['slimDesktop']);
        }

        if (array_key_exists('slimMobile', $attributes)) {
            if (!is_null($attributes['slimMobile'])) {
                $slimMobileData = $attributes['slimMobile'];
            }
            unset($attributes['slimMobile']);
        }

        // 移除前端的清除標記
        if (array_key_exists('slimDesktopCleared', $attributes)) {
            unset($attributes['slimDesktopCleared']);
        }
        if (array_key_exists('slimMobileCleared', $attributes)) {
            unset($attributes['slimMobileCleared']);
        }

        // 2. 儲存主資料
        $banner = parent::save($attributes, $id);

        // 3. 重新載入 banner 以確保關聯已載入
        if ($id) {
            $banner = $banner->fresh(['desktopImage', 'mobileImage']);
        }

        // 4. 處理桌機版圖片
        if ($slimDesktopData) {
            $imageData = ['desktop_image' => $slimDesktopData];
            // 取得舊圖片（編輯時才有）
            $oldImage = $banner->desktopImage ?? null;

            $this->imgRepository->saveSlimFile(
                $imageData,              // $imageData
                $banner,                 // $class (model)
                $oldImage,               // $image (舊圖片)
                'banners/desktop',       // $path
                false,                   // $compress (✅ Banner 不壓縮)
                false                    // $generateThumbnail
            );
        }

        // 5. 處理手機版圖片
        if ($slimMobileData) {
            $imageData = ['mobile_image' => $slimMobileData];
            // 取得舊圖片（編輯時才有）
            $oldImage = $banner->mobileImage ?? null;

            $this->imgRepository->saveSlimFile(
                $imageData,              // $imageData
                $banner,                 // $class (model)
                $oldImage,               // $image (舊圖片)
                'banners/mobile',        // $path
                false,                   // $compress (✅ Banner 不壓縮)
                false                    // $generateThumbnail
            );
        }

        return $banner;
    }

    /**
     * 後台 Banner 列表（分頁）
     */
    public function paginate($perPage, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        return $this->model
            ->with(['desktopImage', 'mobileImage'])
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('id', 'desc')
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($banner) => [
                'id' => $banner->id,
                'title_zh' => $banner->getTranslation('title', 'zh_TW'),
                'title_en' => $banner->getTranslation('title', 'en'),
                'subtitle_1_zh' => $banner->getTranslation('subtitle_1', 'zh_TW'),
                'subtitle_1_en' => $banner->getTranslation('subtitle_1', 'en'),
                'subtitle_2_zh' => $banner->getTranslation('subtitle_2', 'zh_TW'),
                'subtitle_2_en' => $banner->getTranslation('subtitle_2', 'en'),
                'url' => $banner->url,
                'is_active' => (bool) $banner->is_active,
                'sort_order' => $banner->sort_order,
                'desktop_image' => $banner->desktopImage ? $this->resolveImageUrl($banner->desktopImage->path) : null,
                'mobile_image' => $banner->mobileImage ? $this->resolveImageUrl($banner->mobileImage->path) : null,
                'created_at' => $banner->created_at->toDateTimeString(),
                'updated_at' => $banner->updated_at->toDateTimeString(),
            ]);
    }

    /**
     * 取得 Banner 詳情（編輯用）
     */
    public function getDetail($id)
    {
        $banner = $this->model->with(['desktopImage', 'mobileImage'])->findOrFail($id);
        
        return [
            'id' => $banner->id,
            'title' => [
                'zh_TW' => $banner->getTranslation('title', 'zh_TW'),
                'en' => $banner->getTranslation('title', 'en'),
            ],
            'subtitle_1' => [
                'zh_TW' => $banner->getTranslation('subtitle_1', 'zh_TW'),
                'en' => $banner->getTranslation('subtitle_1', 'en'),
            ],
            'subtitle_2' => [
                'zh_TW' => $banner->getTranslation('subtitle_2', 'zh_TW'),
                'en' => $banner->getTranslation('subtitle_2', 'en'),
            ],
            'tags' => [
                'zh_TW' => $banner->getTranslation('tags', 'zh_TW'),
                'en' => $banner->getTranslation('tags', 'en'),
            ],
            'url' => $banner->url,
            'is_active' => (bool) $banner->is_active,
            'sort_order' => $banner->sort_order,
            'desktop_image' => $banner->desktopImage ? '/' . $banner->desktopImage->path : null,
            'mobile_image' => $banner->mobileImage ? '/' . $banner->mobileImage->path : null,
        ];
    }

    /**
     * 取得前台 Banner 列表
     */
    public function getActiveBanners()
    {
        return $this->model
            ->with(['desktopImage', 'mobileImage'])
            ->active()
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($banner) {
                $locale = app()->getLocale();
                return [
                    'id' => $banner->id,
                    'title' => $banner->getTranslation('title', $locale),
                    'subtitle_1' => $banner->getTranslation('subtitle_1', $locale),
                    'subtitle_2' => $banner->getTranslation('subtitle_2', $locale),
                    'tags' => $banner->getTranslation('tags', $locale),
                    'url' => $banner->url,
                    'desktop_image' => $banner->desktopImage ? $this->resolveImageUrl($banner->desktopImage->path) : null,
                    'mobile_image' => $banner->mobileImage ? $this->resolveImageUrl($banner->mobileImage->path) : null,
                ];
            });
    }
}
