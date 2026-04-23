<?php

namespace Modules\SalesLocationImage\Backend\Repository;

use App\Repositories\BaseRepository;
use App\Repositories\ImageRepository;
use Modules\SalesLocationImage\Model\SalesLocationImage;

/**
 * SalesLocationImage 銷售據點圖片管理 - Repository
 */
class SalesLocationImageRepository extends BaseRepository
{
    public function __construct(
        SalesLocationImage $model,
        private ImageRepository $imgRepository
    ) {
        parent::__construct($model);
    }

    /**
     * 取得設定（自動建立）
     */
    public function getSetting()
    {
        return $this->model->firstOrCreate(['id' => 1]);
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getDetail()
    {
        $item = $this->getSetting()->load(['imageZh', 'imageEn']);

        return [
            'id' => $item->id,
            'image_zh' => $item->imageZh ? '/' . $item->imageZh->path : null,
            'image_en' => $item->imageEn ? '/' . $item->imageEn->path : null,
        ];
    }

    /**
     * 儲存設定
     */
    public function saveSetting(array $attributes)
    {
        // 取得或建立設定
        $record = $this->getSetting();

        // 拆出 Slim 圖片資料
        $slimImageZhData = null;
        $slimImageEnData = null;

        if (array_key_exists('image_zh', $attributes)) {
            if (!is_null($attributes['image_zh'])) {
                $slimImageZhData = $attributes['image_zh'];
            }
            unset($attributes['image_zh']);
        }

        if (array_key_exists('image_en', $attributes)) {
            if (!is_null($attributes['image_en'])) {
                $slimImageEnData = $attributes['image_en'];
            }
            unset($attributes['image_en']);
        }

        // 移除清除標記
        unset($attributes['slimImageZhCleared'], $attributes['slimImageEnCleared']);

        // 更新主資料
        $record->update($attributes);
        $record = $record->fresh(['imageZh', 'imageEn']);

        // 處理中文版圖片
        if ($slimImageZhData) {
            $this->imgRepository->saveSlimFile(
                ['image_zh' => $slimImageZhData],
                $record,
                $record->imageZh ?? null,
                'sales-location-image/zh',
                true,
                true
            );
        }

        // 處理英文版圖片
        if ($slimImageEnData) {
            $this->imgRepository->saveSlimFile(
                ['image_en' => $slimImageEnData],
                $record,
                $record->imageEn ?? null,
                'sales-location-image/en',
                true,
                true
            );
        }

        return [
            'status' => true,
            'msg' => '儲存成功',
        ];
    }
}
