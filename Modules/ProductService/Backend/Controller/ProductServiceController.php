<?php

namespace Modules\ProductService\Backend\Controller;

use App\Http\Controllers\Controller;
use App\Models\ProductService;
use Modules\ProductService\Backend\Request\ProductServiceRequest;
use Modules\ProductService\Backend\Service\ProductServiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductServiceController extends Controller
{
    protected ProductServiceService $service;

    public function __construct(ProductServiceService $service)
    {
        $this->service = $service;
    }

    /**
     * 列表頁
     */
    public function index(Request $request)
    {
        $list = $this->service->getList($request);

        return Inertia::render('Admin/ProductService/Index', [
            'list' => $list,
        ]);
    }

    /**
     * 新增頁面
     */
    public function create()
    {
        return Inertia::render('Admin/ProductService/Form', [
            'data' => null,
            'isEdit' => false,
        ]);
    }

    /**
     * 新增儲存
     */
    public function store(ProductServiceRequest $request)
    {
        $result = $this->service->store($request->validated());
        $result['redirect'] = route('admin.product-services.index');

        return Inertia::render('Admin/ProductService/Form', [
            'result' => $result,
        ]);
    }

    /**
     * 編輯頁面
     */
    public function edit($id)
    {
        $data = $this->service->getFormData($id);

        return Inertia::render('Admin/ProductService/Form', [
            'data' => $data,
            'isEdit' => true,
        ]);
    }

    /**
     * 更新儲存
     */
    public function update(ProductServiceRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());
        $result['redirect'] = route('admin.product-services.index');

        return Inertia::render('Admin/ProductService/Form', [
            'result' => $result,
            'data' => $this->service->getFormData($id),
            'isEdit' => true,
        ]);
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $result = $this->service->destroy($id);

        return redirect()->route('admin.product-services.index')
            ->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $result = $this->service->toggleActive($request->id);

        return response()->json($result);
    }

    /**
     * 更新排序
     */
    public function updateSort(Request $request)
    {
        $result = $this->service->updateSort($request->items);

        return response()->json($result);
    }
}
