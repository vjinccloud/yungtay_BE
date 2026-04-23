<?php

namespace Modules\HomeImageSetting\Backend\Repository;

use App\Repositories\BaseRepository;
use App\Repositories\ImageRepository;
use Modules\HomeImageSetting\Model\HomeImageSetting;

/**
 * HomeImageSetting 首頁圖片設定 - Repository
 */
class HomeImageSettingRepository extends BaseRepository
{
    public function __construct(
        HomeImageSetting $model,
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
            'title' => [
                'zh_TW' => $item->getTranslation('title', 'zh_TW'),
                'en' => $item->getTranslation('title', 'en'),
            ],
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

        if (array_key_exists('slimImageZh', $attributes)) {
            if (!is_null($attributes['slimImageZh'])) {
                $slimImageZhData = $attributes['slimImageZh'];
            }
            unset($attributes['slimImageZh']);
        }

        if (array_key_exists('slimImageEn', $attributes)) {
            if (!is_null($attributes['slimImageEn'])) {
                $slimImageEnData = $attributes['slimImageEn'];
            }
            unset($attributes['slimImageEn']);
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
                'home-image-setting/zh',
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
                'home-image-setting/en',
                true,
                true
            );
        }

        return $record;
    }
}
