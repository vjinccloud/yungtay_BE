<?php

namespace App\Services;

use App\Repositories\ExpertArticleRepository;

class ExpertArticleService extends BaseService
{
    public function __construct(
        private ExpertArticleRepository $articleRepository,
    ) {
        parent::__construct($articleRepository);
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
        return $this->articleRepository->getDetail($id);
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '專家文章';
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('title', 'zh_TW') ?? '';
        $model->event_status_title = "專家文章{$action}-{$title}";
    }

    /**
     * 更新排序
     */
    public function updateSort(array $sortedIds, ?string $column = null)
    {
        try {
            $this->articleRepository->updateSort($sortedIds);
            return $this->returnHandle(true, '排序更新成功');
        } catch (\Exception $e) {
            \Log::error('Expert article sort update failed', [
                'error' => $e->getMessage(),
                'data' => $sortedIds
            ]);
            return $this->returnHandle(false, '排序更新失敗：' . $e->getMessage());
        }
    }
}
