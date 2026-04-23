<?php

namespace App\Services;

use App\Repositories\ExpertFieldRepository;

class ExpertFieldService extends BaseService
{
    public function __construct(
        private ExpertFieldRepository $fieldRepository,
    ) {
        parent::__construct($fieldRepository);
    }

    /**
     * 取得所有啟用的領域
     */
    public function getActiveFields()
    {
        return $this->fieldRepository->getActiveFields();
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '專家領域';
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('name', 'zh_TW') ?? '';
        $model->event_status_title = "專家領域{$action}-{$title}";
    }

    /**
     * 更新排序
     */
    public function updateSort(array $sortedIds, ?string $column = null)
    {
        try {
            $this->fieldRepository->updateSort($sortedIds);
            return $this->returnHandle(true, '排序更新成功');
        } catch (\Exception $e) {
            \Log::error('Expert field sort update failed', [
                'error' => $e->getMessage(),
                'data' => $sortedIds
            ]);
            return $this->returnHandle(false, '排序更新失敗：' . $e->getMessage());
        }
    }
}
