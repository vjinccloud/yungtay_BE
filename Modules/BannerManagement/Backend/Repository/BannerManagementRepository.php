<?php

namespace Modules\BannerManagement\Backend\Repository;

use App\Models\Banner;
use App\Repositories\BaseRepository;
use App\Repositories\ImageRepository;

class BannerManagementRepository extends BaseRepository
{
    public function __construct(
        Banner $model,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($model);
    }

    /**
     * 取得列表（分頁）
     */
    public function getListPaginated($request, int $perPage = 20)
    {
        $query = $this->model->newQuery()
            ->with(['desktopImage', 'mobileImage'])
            ->orderBy('sort_order')
            ->orderBy('id', 'desc');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title->zh_TW', 'like', "%{$keyword}%")
                  ->orWhere('url', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->input('status'));
        }

        return $query->paginate($perPage);
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getDetail(int $id): array
    {
        $item = $this->model->with(['desktopImage', 'mobileImage'])->findOrFail($id);

        return [
            'id' => $item->id,
            'title' => $item->getTranslation('title', 'zh_TW') ?? '',
            'url' => $item->url ?? '',
            'is_active' => (bool) $item->is_active,
            'sort_order' => $item->sort_order ?? 0,
            'desktop_image' => $item->desktopImage ? '/' . $item->desktopImage->path : null,
            'mobile_image' => $item->mobileImage ? '/' . $item->mobileImage->path : null,
        ];
    }

    /**
     * 儲存（含圖片處理）
     */
    public function store(array $attributes): Banner
    {
        // 自動設定排序值
        if (!isset($attributes['sort_order'])) {
            $attributes['sort_order'] = ($this->model->max('sort_order') ?? 0) + 1;
        }

        return $this->saveWithImages($attributes, null);
    }

    /**
     * 更新（含圖片處理）
     */
    public function updateById(int $id, array $attributes): Banner
    {
        return $this->saveWithImages($attributes, $id);
    }

    /**
     * 儲存資料與圖片
     */
    protected function saveWithImages(array $attributes, ?int $id): Banner
    {
        // 拆出 Slim 圖片資料
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

        unset($attributes['slimDesktopCleared'], $attributes['slimMobileCleared']);

        // 將 title 字串轉換為多語言 JSON
        if (isset($attributes['title']) && is_string($attributes['title'])) {
            $attributes['title'] = ['zh_TW' => $attributes['title']];
        }

        // 儲存主資料
        if ($id) {
            $record = $this->model->findOrFail($id);
            $record->update($attributes);
        } else {
            $record = $this->model->create($attributes);
        }

        $record = $record->fresh(['desktopImage', 'mobileImage']);

        // 處理桌機版圖片
        if ($slimDesktopData) {
            $this->imgRepository->saveSlimFile(
                ['desktop_image' => $slimDesktopData],
                $record,
                $record->desktopImage ?? null,
                'banners/desktop',
                false,
                false
            );
        }

        // 處理手機版圖片
        if ($slimMobileData) {
            $this->imgRepository->saveSlimFile(
                ['mobile_image' => $slimMobileData],
                $record,
                $record->mobileImage ?? null,
                'banners/mobile',
                false,
                false
            );
        }

        return $record;
    }

    /**
     * 刪除（含圖片清理）
     */
    public function deleteWithImages(int $id): bool
    {
        $record = $this->model->with(['desktopImage', 'mobileImage'])->findOrFail($id);

        if ($record->desktopImage) {
            $this->imgRepository->deleteImgFile($record->desktopImage);
            $this->imgRepository->deleteImg($record->desktopImage);
        }

        if ($record->mobileImage) {
            $this->imgRepository->deleteImgFile($record->mobileImage);
            $this->imgRepository->deleteImg($record->mobileImage);
        }

        return $record->delete();
    }
}
