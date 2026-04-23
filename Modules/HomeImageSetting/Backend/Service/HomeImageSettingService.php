<?php

namespace Modules\HomeImageSetting\Backend\Service;

use Modules\HomeImageSetting\Backend\Repository\HomeImageSettingRepository;

/**
 * HomeImageSetting 首頁圖片設定 - Service
 */
class HomeImageSettingService
{
    public function __construct(
        private HomeImageSettingRepository $repository
    ) {}

    /**
     * 取得設定資料（編輯用）
     */
    public function getFormData()
    {
        return $this->repository->getDetail();
    }

    /**
     * 儲存設定
     */
    public function save(array $attributes)
    {
        $this->repository->saveSetting($attributes);

        return [
            'status' => true,
            'msg' => '儲存成功'
        ];
    }
}
