<?php

namespace Modules\IntroVideo\Backend\Service;

use Modules\IntroVideo\Backend\Repository\IntroVideoRepository;

/**
 * IntroVideo 片頭動畫 - Service
 */
class IntroVideoService
{
    public function __construct(
        private IntroVideoRepository $repository
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
    public function save(array $attributes, $videoFile = null)
    {
        $this->repository->saveSetting($attributes, $videoFile);

        return [
            'status' => true,
            'msg' => '儲存成功'
        ];
    }

    /**
     * 刪除影片
     */
    public function deleteVideo()
    {
        $this->repository->deleteVideo();

        return [
            'status' => true,
            'msg' => '影片已刪除'
        ];
    }
}
