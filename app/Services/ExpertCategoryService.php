<?php

namespace App\Services;

use App\Repositories\ExpertCategoryRepository;

class ExpertCategoryService extends BaseService
{
    public function __construct(
        private ExpertCategoryRepository $categoryRepository,
    ) {
        parent::__construct($categoryRepository);
    }

    /**
     * 取得所有啟用的分類
     */
    public function getActiveCategories()
    {
        return $this->categoryRepository->getActiveCategories();
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '專家分類';
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('name', 'zh_TW') ?? '';
        $model->event_status_title = "專家分類{$action}-{$title}";
    }

    /**
     * 更新排序
     */
    public function updateSort(array $sortedIds, ?string $column = null)
    {
        try {
            $this->categoryRepository->updateSort($sortedIds);
            return $this->returnHandle(true, '排序更新成功');
        } catch (\Exception $e) {
            \Log::error('Expert category sort update failed', [
                'error' => $e->getMessage(),
                'data' => $sortedIds
            ]);
            return $this->returnHandle(false, '排序更新失敗：' . $e->getMessage());
        }
    }
}
