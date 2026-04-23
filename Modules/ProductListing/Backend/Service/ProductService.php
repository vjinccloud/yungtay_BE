<?php

namespace Modules\ProductListing\Backend\Service;

use Modules\ProductListing\Backend\Repository\ProductRepository;
use Modules\ProductSpecSetting\Backend\Service\ProductSpecService;
use Modules\FrontMenuSetting\Backend\Service\FrontMenuService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private ProductSpecService $specService,
        private FrontMenuService $menuService
    ) {}

    // ========================================
    // Product List
    // ========================================

    public function getProductList()
    {
        $products = $this->repository->getAllProductsOrdered();
        return $products->map(fn ($p) => $this->formatProductListItem($p))->values()->toArray();
    }

    public function getProductListPaginated($request)
    {
        $filters = [
            'keyword'     => $request->input('keyword'),
            'status'      => $request->input('status'),
            'category_id' => $request->input('category_id'),
            'is_hot'      => $request->input('is_hot'),
            'type'        => $request->input('type'),
        ];
        $perPage = (int) $request->input('per_page', 15);

        $paginated = $this->repository->getProductsPaginated($filters, $perPage);

        $paginated->getCollection()->transform(fn ($p) => $this->formatProductListItem($p));

        return $paginated;
    }

    // ========================================
    // Product Form Data
    // ========================================

    public function getProductFormData($id)
    {
        $product = $this->repository->findProductOrFail($id);
        $product->load(['images', 'skus', 'categories', 'specCombination.combinationGroups.group.values']);

        $locales = array_keys(config('translatable.locales', ['zh_TW' => []]));

        $name = [];
        $description = [];
        foreach ($locales as $locale) {
            $name[$locale]        = $product->getTranslation('name', $locale) ?? '';
            $description[$locale] = $product->getTranslation('description', $locale) ?? '';
        }

        return [
            'id'    => $product->id,
            'name'  => $name,
            'type'  => $product->type ?? 'regular',
            'status' => $product->status,
            'price'  => $product->price,
            'stock'  => $product->stock,
            'is_hot' => $product->is_hot,
            'seq'    => $product->seq,
            'category_ids'        => $product->categories->pluck('id')->toArray(),
            'spec_combination_id' => $product->spec_combination_id,
            'description' => $description,
            'main_image'     => $product->images->where('type', 'main')->first()?->image_path,
            'gallery_images' => $product->images->where('type', 'gallery')->values()->map(fn ($img) => [
                'id'         => $img->id,
                'image_path' => $img->image_path,
                'seq'        => $img->seq,
            ])->toArray(),
            'skus' => $product->skus->map(fn ($sku) => [
                'id'                => $sku->id,
                'spec_value_ids'    => $sku->spec_value_ids,
                'combination_label' => $sku->combination_label,
                'sku'               => $sku->sku,
                'price'             => $sku->price,
                'stock'             => $sku->stock,
                'status'            => $sku->status,
            ])->toArray(),
        ];
    }

    // ========================================
    // Store
    // ========================================

    public function storeProduct(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 建立商品
            $product = $this->repository->createProduct([
                'name'                => $data['name'],
                'type'                => $data['type'] ?? 'regular',
                'status'              => $data['status'] ?? 1,
                'price'               => $data['price'] ?? 0,
                'stock'               => $data['stock'] ?? 0,
                'is_hot'              => $data['is_hot'] ?? false,
                'spec_combination_id' => $data['spec_combination_id'] ?? null,
                'description'         => $data['description'] ?? null,
                'seq'                 => $data['seq'] ?? 0,
            ]);

            // 同步分類（贈品不需要分類）
            if (($data['type'] ?? 'regular') === 'gift') {
                $product->categories()->sync([]);
            } elseif (isset($data['category_ids'])) {
                $product->categories()->sync($data['category_ids']);
            }

            // 處理主圖
            if (!empty($data['main_image'])) {
                $this->saveImage($product->id, $data['main_image'], 'main', 0);
            }

            // 處理多張圖
            if (!empty($data['gallery_images'])) {
                foreach ($data['gallery_images'] as $i => $img) {
                    $path = is_array($img) ? ($img['image_path'] ?? null) : $img;
                    if ($path) {
                        $this->saveImage($product->id, $path, 'gallery', $i);
                    }
                }
            }

            // 處理 SKU
            if (!empty($data['skus'])) {
                foreach ($data['skus'] as $skuData) {
                    $this->repository->createSku([
                        'product_id'        => $product->id,
                        'spec_value_ids'    => $skuData['spec_value_ids'],
                        'combination_label' => $skuData['combination_label'] ?? '',
                        'sku'               => $skuData['sku'] ?? null,
                        'price'             => $skuData['price'] ?? 0,
                        'stock'             => $skuData['stock'] ?? 0,
                        'status'            => $skuData['status'] ?? true,
                    ]);
                }
            }

            return ['status' => true, 'msg' => '商品新增成功'];
        });
    }

    // ========================================
    // Update
    // ========================================

    public function updateProduct($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $this->repository->updateProduct($id, [
                'name'                => $data['name'],
                'type'                => $data['type'] ?? 'regular',
                'status'              => $data['status'] ?? 1,
                'price'               => $data['price'] ?? 0,
                'stock'               => $data['stock'] ?? 0,
                'is_hot'              => $data['is_hot'] ?? false,
                'spec_combination_id' => $data['spec_combination_id'] ?? null,
                'description'         => $data['description'] ?? null,
                'seq'                 => $data['seq'] ?? 0,
            ]);

            // 同步分類（贈品不需要分類）
            $product = $this->repository->findProductOrFail($id);
            if (($data['type'] ?? 'regular') === 'gift') {
                $product->categories()->sync([]);
            } else {
                $product->categories()->sync($data['category_ids'] ?? []);
            }

            // 更新主圖
            if (array_key_exists('main_image', $data)) {
                $this->repository->deleteImagesByProductIdAndType($id, 'main');
                if (!empty($data['main_image'])) {
                    $this->saveImage($id, $data['main_image'], 'main', 0);
                }
            }

            // 更新多張圖
            if (array_key_exists('gallery_images', $data)) {
                $this->repository->deleteImagesByProductIdAndType($id, 'gallery');
                if (!empty($data['gallery_images'])) {
                    foreach ($data['gallery_images'] as $i => $img) {
                        $path = is_array($img) ? ($img['image_path'] ?? null) : $img;
                        if ($path) {
                            $this->saveImage($id, $path, 'gallery', $i);
                        }
                    }
                }
            }

            // 更新 SKU (全刪重建)
            if (array_key_exists('skus', $data)) {
                $this->repository->deleteSkusByProductId($id);
                if (!empty($data['skus'])) {
                    foreach ($data['skus'] as $skuData) {
                        $this->repository->createSku([
                            'product_id'        => $id,
                            'spec_value_ids'    => $skuData['spec_value_ids'],
                            'combination_label' => $skuData['combination_label'] ?? '',
                            'sku'               => $skuData['sku'] ?? null,
                            'price'             => $skuData['price'] ?? 0,
                            'stock'             => $skuData['stock'] ?? 0,
                            'status'            => $skuData['status'] ?? true,
                        ]);
                    }
                }
            }

            return ['status' => true, 'msg' => '商品更新成功'];
        });
    }

    // ========================================
    // Delete
    // ========================================

    public function destroyProduct($id)
    {
        return DB::transaction(function () use ($id) {
            $this->repository->deleteProduct($id);
            return ['status' => true, 'msg' => '商品刪除成功'];
        });
    }

    // ========================================
    // Toggle / Sort
    // ========================================

    public function toggleProductActive($id)
    {
        $product = $this->repository->findProductOrFail($id);
        $product->status = $product->status ? 0 : 1;
        $product->save();
        return ['status' => true, 'msg' => $product->status ? '已上架' : '已下架'];
    }

    public function updateProductSort(array $items)
    {
        $this->repository->batchUpdateProductSort($items);
        return ['status' => true, 'msg' => '排序更新成功'];
    }

    // ========================================
    // Spec Combination → Auto-generate SKUs
    // ========================================

    /**
     * 根據 spec_combination_id 自動產生 SKU 矩陣
     * 回傳所有值的笛卡爾積
     */
    public function generateSkuMatrix($combinationId)
    {
        $combo = \Modules\ProductSpecSetting\Model\SpecCombination::with([
            'combinationGroups.group.values' => fn ($q) => $q->active()->ordered(),
        ])->findOrFail($combinationId);

        $groups = $combo->combinationGroups
            ->sortBy(fn ($cg) => $cg->group->seq ?? 0)
            ->values();

        // 收集每個群組的值
        $groupValues = [];
        foreach ($groups as $cg) {
            $vals = $cg->group->values->map(fn ($v) => [
                'id'      => $v->id,
                'name_zh' => $v->getTranslation('name', 'zh_TW'),
                'name_en' => $v->getTranslation('name', 'en'),
            ])->toArray();

            if (empty($vals)) continue;

            $groupValues[] = [
                'group_id'   => $cg->group->id,
                'group_name' => $cg->group->getTranslation('name', 'zh_TW'),
                'values'     => $vals,
            ];
        }

        if (empty($groupValues)) {
            return ['status' => true, 'data' => []];
        }

        // 笛卡爾積
        $matrix = $this->cartesianProduct($groupValues);

        return ['status' => true, 'data' => $matrix];
    }

    /**
     * 笛卡爾積 — 組合所有群組的值
     */
    protected function cartesianProduct(array $groupValues): array
    {
        $result = [[]];

        foreach ($groupValues as $groupData) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($groupData['values'] as $val) {
                    $entry = $existing;
                    $entry[] = [
                        'group_id'   => $groupData['group_id'],
                        'group_name' => $groupData['group_name'],
                        'value_id'   => $val['id'],
                        'value_name' => $val['name_zh'],
                    ];
                    $newResult[] = $entry;
                }
            }
            $result = $newResult;
        }

        // 格式化為前端可用結構
        return array_map(function ($items) {
            $valueIds = array_column($items, 'value_id');
            $labels   = array_column($items, 'value_name');
            return [
                'spec_value_ids'    => $valueIds,
                'combination_label' => implode(' / ', $labels),
                'items'             => $items,
                'sku'               => '',
                'price'             => 0,
                'stock'             => 0,
                'status'            => true,
            ];
        }, $result);
    }

    // ========================================
    // Get Spec Combinations for dropdown
    // ========================================

    public function getSpecCombinationsForSelect()
    {
        $combos = $this->specService->getCombinationList();
        return array_map(fn ($c) => [
            'id'       => $c['id'],
            'name_zh'  => $c['name_zh'],
            'label'    => $c['label'],
            'group_ids' => $c['group_ids'],
        ], $combos);
    }

    // ========================================
    // Get Categories for dropdown
    // ========================================

    public function getCategoriesForSelect()
    {
        $tree = $this->menuService->getTreeList();
        return $this->buildCategoryTree($tree);
    }

    /**
     * 將 FrontMenu 樹狀結構轉為分類勾選樹
     */
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

    // ========================================
    // Helper
    // ========================================

    protected function formatProductListItem($product)
    {
        $primary = config('translatable.primary', 'zh_TW');

        return [
            'id'         => $product->id,
            'name'       => $product->name,
            'name_primary' => $product->getTranslation('name', $primary),
            'type'       => $product->type ?? 'regular',
            'status'     => $product->status,
            'price'      => $product->price,
            'is_hot'     => $product->is_hot,
            'seq'        => $product->seq,
            'category_name' => $product->categories->isNotEmpty()
                ? $product->categories->map(fn ($c) => $c->getTranslation('title', $primary))->implode(', ')
                : null,
            'main_image' => $product->mainImage?->image_path,
            'combo_name' => $product->specCombination
                ? $product->specCombination->getTranslation('name', $primary)
                : null,
            'created_at' => $product->created_at?->format('Y-m-d H:i'),
        ];
    }

    protected function saveImage($productId, $path, $type, $seq)
    {
        // 如果是上傳的 UploadedFile，存到 storage
        if ($path instanceof \Illuminate\Http\UploadedFile) {
            $storedPath = $path->store('products', 'public');
            $path = '/storage/' . $storedPath;
        }

        $this->repository->createImage([
            'product_id' => $productId,
            'image_path' => $path,
            'type'       => $type,
            'seq'        => $seq,
        ]);
    }
}
