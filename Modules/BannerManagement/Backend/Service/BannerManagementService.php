<?php

namespace Modules\BannerManagement\Backend\Service;

use Modules\BannerManagement\Backend\Repository\BannerManagementRepository;

class BannerManagementService
{
    public function __construct(
        private BannerManagementRepository $repository
    ) {}

    /**
     * 取得列表（分頁）
     */
    public function getListPaginated($request)
    {
        $paginated = $this->repository->getListPaginated($request);

        return $paginated->through(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->getTranslation('title', 'zh_TW') ?? '',
                'url' => $item->url,
                'is_active' => (bool) $item->is_active,
                'sort_order' => $item->sort_order,
                'desktop_image' => $item->desktopImage ? '/' . $item->desktopImage->path : null,
                'mobile_image' => $item->mobileImage ? '/' . $item->mobileImage->path : null,
                'updated_at' => $item->updated_at?->format('Y-m-d H:i'),
            ];
        });
    }

    /**
     * 取得表單資料（編輯用）
     */
    public function getFormData(int $id): array
    {
        return $this->repository->getDetail($id);
    }

    /**
     * 建立
     */
    public function store(array $data): array
    {
        $this->repository->store($data);
        return ['status' => true, 'msg' => '新增成功'];
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): array
    {
        $this->repository->updateById($id, $data);
        return ['status' => true, 'msg' => '更新成功'];
    }

    /**
     * 刪除
     */
    public function destroy(int $id): array
    {
        $this->repository->deleteWithImages($id);
        return ['status' => true, 'msg' => '刪除成功'];
    }
}
