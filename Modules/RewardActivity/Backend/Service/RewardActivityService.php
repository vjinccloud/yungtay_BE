<?php

namespace Modules\RewardActivity\Backend\Service;

use Modules\RewardActivity\Backend\Repository\RewardActivityRepository;
use Modules\FrontMenuSetting\Backend\Service\FrontMenuService;

class RewardActivityService
{
    public function __construct(
        private RewardActivityRepository $repository,
        private FrontMenuService $menuService
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
                'title' => $item->title,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'status' => $item->status,
                'show_on_frontend' => $item->show_on_frontend,
                'promo_code' => $item->promo_code,
                'reward_type' => $item->reward_type,
                'reward_value' => $item->reward_value,
                'condition_type' => $item->condition_type,
                'redemption_limit_type' => $item->redemption_limit_type,
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
     * 取得商品分類樹（供 TreeCheckbox 使用）
     */
    public function getCategoriesForSelect(): array
    {
        $tree = $this->menuService->getTreeList();
        return $this->buildCategoryTree($tree);
    }

    /**
     * 建立
     */
    public function store(array $data): array
    {
        $this->cleanData($data);
        $this->repository->store($data);
        return ['status' => true, 'msg' => '新增成功'];
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): array
    {
        $this->cleanData($data);
        $this->repository->updateById($id, $data);
        return ['status' => true, 'msg' => '更新成功'];
    }

    /**
     * 刪除
     */
    public function destroy(int $id): array
    {
        $this->repository->delete($id);
        return ['status' => true, 'msg' => '刪除成功'];
    }

    /**
     * 清理條件性欄位
     */
    protected function cleanData(array &$data): void
    {
        // 條件類型不是 order_total 時清除金額
        if (($data['condition_type'] ?? '') !== 'order_total') {
            $data['condition_order_total'] = null;
        }
        // 條件類型不是 category 時清除分類
        if (($data['condition_type'] ?? '') !== 'category') {
            $data['condition_category_ids'] = null;
        }
        // 獎勵類型不是購物金時重置購物金相關欄位
        if (($data['reward_type'] ?? '') !== 'shopping_credit') {
            $data['credit_expiry_type'] = 'unlimited';
            $data['credit_expiry_days'] = null;
        }
        // 有效期限不是 days 時清除天數
        if (($data['credit_expiry_type'] ?? '') !== 'days') {
            $data['credit_expiry_days'] = null;
        }
        // 回饋次數不是 site_total 時清除次數
        if (($data['redemption_limit_type'] ?? '') !== 'site_total') {
            $data['redemption_site_total'] = null;
        }
    }

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
