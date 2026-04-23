<?php

namespace App\Services;

use App\Repositories\ExpertRepository;

class ExpertService extends BaseService
{
    public function __construct(
        private ExpertRepository $expertRepository,
    ) {
        parent::__construct($expertRepository);
    }

    /**
     * 覆寫 hasImages 表示這個模型有圖片
     */
    protected function hasImages($model)
    {
        return true;
    }

    /**
     * 自訂圖片刪除邏輯
     */
    protected function deleteRelatedImages($model)
    {
        if ($model->image) {
            $this->imageRepository->deleteImgFile($model->image);
            $this->imageRepository->deleteImg($model->image);
        }
    }

    /**
     * 取得表單資料
     */
    public function getFormData($id)
    {
        return $this->expertRepository->getDetail($id);
    }

    /**
     * 取得所有啟用的專家
     */
    public function getActiveExperts()
    {
        return $this->expertRepository->getActiveExperts();
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '專家管理';
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('name', 'zh_TW') ?? '';
        $model->event_status_title = "專家{$action}-{$title}";
    }

    /**
     * 更新排序
     */
    public function updateSort(array $sortedIds, ?string $column = null)
    {
        try {
            $this->expertRepository->updateSort($sortedIds);
            return $this->returnHandle(true, '排序更新成功');
        } catch (\Exception $e) {
            \Log::error('Expert sort update failed', [
                'error' => $e->getMessage(),
                'data' => $sortedIds
            ]);
            return $this->returnHandle(false, '排序更新失敗：' . $e->getMessage());
        }
    }
}
