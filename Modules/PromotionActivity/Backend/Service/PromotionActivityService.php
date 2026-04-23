<?php

namespace Modules\PromotionActivity\Backend\Service;

use Modules\PromotionActivity\Backend\Repository\PromotionActivityRepository;
use Modules\FrontMenuSetting\Backend\Service\FrontMenuService;

class PromotionActivityService
{
    public function __construct(
        private PromotionActivityRepository $repository,
        private FrontMenuService $menuService
    ) {}

    /**
     * 取得設定資料（編輯用）
     */
    public function getFormData(): array
    {
        return $this->repository->getDetail();
    }

    /**
     * 取得商品分類樹（供 TreeCheckbox 使用）
     */
    public function getCategoriesForSelect(): array
    {
        $tree = $this->menuService->getTreeList();
        return $this->buildCategoryTree($tree);
    }

    /**
     * 儲存設定
     */
    public function save(array $attributes): array
    {
        $this->repository->saveSetting($attributes);
        return [
            'status' => true,
            'msg' => '儲存成功',
        ];
    }

    /**
     * 將前台選單樹狀結構轉為分類勾選樹
     */
    protected function buildCategoryTree(array $nodes): array
    {
        return array_map(function ($node) {
            $item = [
                'id' => $node['id'],
                'label' => $node['title_primary'] ?? $node['title'] ?? '',
                'children' => [],
            ];
            if (!empty($node['children'])) {
                $item['children'] = $this->buildCategoryTree($node['children']);
            }
            return $item;
        }, $nodes);
    }
}
