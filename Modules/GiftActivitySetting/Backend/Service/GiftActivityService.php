<?php

namespace Modules\GiftActivitySetting\Backend\Service;

use Modules\GiftActivitySetting\Backend\Repository\GiftActivityRepository;
use Modules\FrontMenuSetting\Backend\Service\FrontMenuService;
use Modules\ProductListing\Model\Product;

class GiftActivityService
{
    public function __construct(
        private GiftActivityRepository $repository,
        private FrontMenuService $menuService
    ) {}

    public function getListPaginated($request)
    {
        $paginated = $this->repository->getListPaginated($request);

        return $paginated->through(function ($item) {
            return [
                'id'             => $item->id,
                'title'          => $item->title,
                'start_date'     => $item->start_date?->format('Y-m-d'),
                'end_date'       => $item->end_date?->format('Y-m-d'),
                'status'         => $item->status,
                'condition_type' => $item->condition_type,
                'gift_count'     => is_array($item->gift_products)
                    ? array_sum(array_column($item->gift_products, 'qty'))
                    : 0,
            ];
        });
    }

    public function getFormData(int $id): array
    {
        return $this->repository->getDetail($id);
    }

    public function getCategoriesForSelect(): array
    {
        $tree = $this->menuService->getTreeList();
        return $this->buildCategoryTree($tree);
    }

    /**
     * 取得所有贈品商品（type=gift）含 SKU 資訊供下拉選擇
     */
    public function getGiftProductsForSelect(): array
    {
        $primary = config('translatable.primary', 'zh_TW');

        return Product::where('type', 'gift')
            ->where('status', 1)
            ->with('skus')
            ->ordered()
            ->get()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'label' => $p->getTranslation('name', $primary),
                'skus'  => $p->skus->map(fn ($sku) => [
                    'id'    => $sku->id,
                    'label' => $sku->combination_label ?: ('SKU: ' . ($sku->sku ?: $sku->id)),
                ])->toArray(),
            ])
            ->toArray();
    }

    public function store(array $data): array
    {
        $this->cleanData($data);
        $this->repository->store($data);
        return ['status' => true, 'msg' => '新增成功'];
    }

    public function update(int $id, array $data): array
    {
        $this->cleanData($data);
        $this->repository->updateById($id, $data);
        return ['status' => true, 'msg' => '更新成功'];
    }

    public function destroy(int $id): array
    {
        $this->repository->delete($id);
        return ['status' => true, 'msg' => '刪除成功'];
    }

    protected function cleanData(array &$data): void
    {
        if (($data['condition_type'] ?? '') !== 'order_total') {
            $data['condition_amount'] = null;
        }
        if (($data['condition_type'] ?? '') !== 'category') {
            $data['condition_category_ids'] = null;
        }
    }

    protected function buildCategoryTree(array $nodes): array
    {
        return array_map(function ($node) {
            $item = [
                'id'       => $node['id'],
                'label'    => $node['title_primary'] ?? $node['title'] ?? '',
                'children' => [],
            ];
            if (!empty($node['children'])) {
                $item['children'] = $this->buildCategoryTree($node['children']);
            }
            return $item;
        }, $nodes);
    }
}
