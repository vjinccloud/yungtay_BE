<?php

namespace Modules\ProductListing\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\ProductListing\Backend\Request\ProductRequest;
use Modules\ProductListing\Backend\Service\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    // ========================================
    // Inertia Pages
    // ========================================

    public function index(Request $request)
    {
        $products   = $this->service->getProductListPaginated($request);
        $categories = $this->service->getCategoriesForSelect();

        return Inertia::render('Admin/ProductListing/Index', [
            'products'   => $products,
            'categories' => $categories,
            'filters'    => [
                'keyword'     => $request->input('keyword', ''),
                'status'      => $request->input('status', ''),
                'category_id' => $request->input('category_id', ''),
                'is_hot'      => $request->input('is_hot', ''),
                'type'        => $request->input('type', ''),
                'per_page'    => (int) $request->input('per_page', 15),
            ],
        ]);
    }

    public function create()
    {
        $combinations = $this->service->getSpecCombinationsForSelect();
        $categories   = $this->service->getCategoriesForSelect();

        return Inertia::render('Admin/ProductListing/Form', [
            'data'         => null,
            'isEdit'       => false,
            'combinations' => $combinations,
            'categories'   => $categories,
        ]);
    }

    public function store(ProductRequest $request)
    {
        $result = $this->service->storeProduct($request->validated());

        return redirect()->route('admin.product-listings.index')
                         ->with('result', $result);
    }

    public function edit($id)
    {
        $data         = $this->service->getProductFormData($id);
        $combinations = $this->service->getSpecCombinationsForSelect();
        $categories   = $this->service->getCategoriesForSelect();

        return Inertia::render('Admin/ProductListing/Form', [
            'data'         => $data,
            'isEdit'       => true,
            'combinations' => $combinations,
            'categories'   => $categories,
        ]);
    }

    public function update(ProductRequest $request, $id)
    {
        $result = $this->service->updateProduct($id, $request->validated());

        return redirect()->route('admin.product-listings.index')
                         ->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->service->destroyProduct($id);

        return redirect()->route('admin.product-listings.index')
                         ->with('result', $result);
    }

    public function toggleActive(Request $request)
    {
        $result = $this->service->toggleProductActive($request->id);
        return response()->json($result);
    }

    public function updateSort(Request $request)
    {
        $result = $this->service->updateProductSort($request->items);
        return response()->json($result);
    }

    // ========================================
    // API — 產生 SKU 矩陣
    // ========================================

    public function apiGenerateSkuMatrix(Request $request)
    {
        $combinationId = $request->input('combination_id');
        if (!$combinationId) {
            return response()->json(['status' => false, 'msg' => '請選擇規格組合']);
        }

        $result = $this->service->generateSkuMatrix($combinationId);
        return response()->json($result);
    }

    // ========================================
    // API — 上傳圖片
    // ========================================

    public function apiUploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('image');
        $path = $file->store('products', 'public');

        return response()->json([
            'status' => true,
            'path'   => '/storage/' . $path,
        ]);
    }
}
