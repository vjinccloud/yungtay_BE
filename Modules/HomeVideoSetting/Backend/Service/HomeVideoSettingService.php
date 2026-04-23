<?php

namespace Modules\HomeVideoSetting\Backend\Service;

use Modules\HomeVideoSetting\Backend\Repository\HomeVideoSettingRepository;

/**
 * HomeVideoSetting 首頁影片管理 - Service
 */
class HomeVideoSettingService
{
    public function __construct(
        private HomeVideoSettingRepository $repository
    ) {}

    /**
     * 取得列表
     */
    public function getList()
    {
        return $this->repository->getList();
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getFormData($id)
    {
        return $this->repository->getDetail($id);
    }

    /**
     * 新增
     */
    public function store(array $attributes)
    {
        $this->repository->store($attributes);

        return [
            'status' => true,
            'msg' => '新增成功'
        ];
    }

    /**
     * 更新
     */
    public function update($id, array $attributes)
    {
        $this->repository->updateRecord($id, $attributes);

        return [
            'status' => true,
            'msg' => '更新成功'
        ];
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $this->repository->destroy($id);

        return [
            'status' => true,
            'msg' => '刪除成功'
        ];
    }

    /**
     * 更新排序
     */
    public function updateSort(array $items)
    {
        $this->repository->updateSort($items);

        return [
            'status' => true,
            'msg' => '排序更新成功'
        ];
    }
}
