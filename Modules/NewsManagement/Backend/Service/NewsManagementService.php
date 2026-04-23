<?php

namespace Modules\NewsManagement\Backend\Service;

use Modules\NewsManagement\Backend\Repository\NewsManagementRepository;
use App\Services\CategoryService;

class NewsManagementService
{
    public function __construct(
        private NewsManagementRepository $repository,
        private CategoryService $categoryService
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
                'category_id' => $item->category_id,
                'category_name' => $item->category?->getTranslation('name', 'zh_TW') ?? '',
                'title' => $item->getTranslation('title', 'zh_TW') ?? '',
                'is_active' => (bool) $item->is_active,
                'is_homepage_featured' => (bool) $item->is_homepage_featured,
                'is_pinned' => (bool) $item->is_pinned,
                'published_date' => $item->published_date?->toDateString(),
                'img' => $item->image ? '/' . $item->image->path : null,
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
     * 取得分類列表
     */
    public function getCategories()
    {
        return $this->categoryService->getCategoriesByType('news');
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

    /**
     * 切換啟用狀態
     */
    public function toggleActive(int $id): array
    {
        $item = $this->repository->find($id);
        $item->is_active = !$item->is_active;
        $item->save();

        $action = $item->is_active ? '啟用' : '停用';
        return ['status' => true, 'msg' => "已{$action}"];
    }

    /**
     * 切換首頁曝光
     */
    public function toggleHomepageFeatured(int $id): array
    {
        $item = $this->repository->find($id);

        if (!$item->is_homepage_featured) {
            $count = $this->repository->countHomepageFeatured();
            if ($count >= 4) {
                return [
                    'status' => false,
                    'msg' => '首頁曝光文章已達上限（最多4則），請先取消其他文章的首頁曝光設定',
                ];
            }
        }

        $item->is_homepage_featured = !$item->is_homepage_featured;
        $item->save();

        return [
            'status' => true,
            'msg' => $item->is_homepage_featured ? '已設為首頁曝光文章' : '已取消首頁曝光',
        ];
    }

    /**
     * 切換置頂
     */
    public function togglePinned(int $id): array
    {
        $item = $this->repository->find($id);

        if (!$item->is_pinned) {
            $count = $this->repository->countPinned();
            if ($count >= 3) {
                return [
                    'status' => false,
                    'msg' => '置頂文章已達上限（最多3則），請先取消其他文章的置頂設定',
                ];
            }
        }

        $item->is_pinned = !$item->is_pinned;
        $item->save();

        return [
            'status' => true,
            'msg' => $item->is_pinned ? '已設為置頂文章' : '已取消置頂',
        ];
    }
}
