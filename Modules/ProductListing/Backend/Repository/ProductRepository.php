<?php

namespace Modules\ProductListing\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\ProductListing\Model\Product;
use Modules\ProductListing\Model\ProductImage;
use Modules\ProductListing\Model\ProductSku;

class ProductRepository extends BaseRepository
{
    protected ProductImage $imageModel;
    protected ProductSku $skuModel;

    public function __construct(
        Product $model,
        ProductImage $imageModel,
        ProductSku $skuModel
    ) {
        parent::__construct($model);
        $this->imageModel = $imageModel;
        $this->skuModel   = $skuModel;
    }

    // ===== Product =====

    public function getAllProductsOrdered()
    {
        return $this->model->with(['mainImage', 'specCombination', 'categories'])->ordered()->get();
    }

    public function getProductsPaginated(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->with(['mainImage', 'specCombination', 'categories']);

        // 商品類型篩選
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // 名稱搜尋
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where('name', 'like', "%{$keyword}%");
        }

        // 狀態篩選
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('status', (int) $filters['status']);
        }

        // 分類篩選
        if (!empty($filters['category_id'])) {
            $catId = (int) $filters['category_id'];
            $query->whereHas('categories', fn ($q) => $q->where('front_menus.id', $catId));
        }

        // 熱銷
        if (isset($filters['is_hot']) && $filters['is_hot'] !== '' && $filters['is_hot'] !== null) {
            $query->where('is_hot', (bool) $filters['is_hot']);
        }

        return $query->ordered()->paginate($perPage);
    }

    public function findProductOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    public function createProduct(array $data)
    {
        return $this->save($data);
    }

    public function updateProduct($id, array $data)
    {
        return $this->save($data, $id);
    }

    public function deleteProduct($id)
    {
        return $this->model->findOrFail($id)->delete();
    }

    public function batchUpdateProductSort(array $items)
    {
        return $this->batchUpdateSort($this->model, $items);
    }

    // ===== Product Images =====

    public function createImage(array $data)
    {
        return $this->imageModel->create($data);
    }

    public function deleteImagesByProductId($productId)
    {
        return $this->imageModel->where('product_id', $productId)->delete();
    }

    public function deleteImagesByProductIdAndType($productId, $type)
    {
        return $this->imageModel->where('product_id', $productId)
                                ->where('type', $type)
                                ->delete();
    }

    public function getImagesByProductId($productId)
    {
        return $this->imageModel->where('product_id', $productId)->orderBy('seq')->get();
    }

    // ===== Product SKU =====

    public function getSkusByProductId($productId)
    {
        return $this->skuModel->where('product_id', $productId)->get();
    }

    public function createSku(array $data)
    {
        return $this->skuModel->create($data);
    }

    public function updateSku($id, array $data)
    {
        $sku = $this->skuModel->findOrFail($id);
        $sku->update($data);
        return $sku;
    }

    public function deleteSkusByProductId($productId)
    {
        return $this->skuModel->where('product_id', $productId)->delete();
    }

    public function findSkuOrFail($id)
    {
        return $this->skuModel->findOrFail($id);
    }
}
