<?php

namespace Modules\FrontMenuSetting\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\FrontMenuSetting\Backend\Request\FrontMenuRequest;
use Modules\FrontMenuSetting\Backend\Service\FrontMenuService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FrontMenuController extends Controller
{
    protected FrontMenuService $service;

    public function __construct(FrontMenuService $service)
    {
        $this->service = $service;
    }

    // =============================================
    // Inertia 頁面（完整頁面 CRUD）
    // =============================================

    /**
     * 列表頁（樹狀結構）
     */
    public function index(Request $request)
    {
        $tree = $this->service->getTreeList();

        return Inertia::render('Admin/FrontMenuSetting/Index', [
            'tree' => $tree,
        ]);
    }

    /**
     * 新增頁面
     */
    public function create()
    {
        $parentOptions = $this->service->getParentOptions();

        return Inertia::render('Admin/FrontMenuSetting/Form', [
            'data' => null,
            'isEdit' => false,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * 新增儲存
     */
    public function store(FrontMenuRequest $request)
    {
        $result = $this->service->store($request->validated());

        return redirect()->route('admin.front-menu-settings.index')
            ->with('result', $result);
    }

    /**
     * 編輯頁面
     */
    public function edit($id)
    {
        $data = $this->service->getFormData($id);
        $parentOptions = $this->service->getParentOptions($id);

        return Inertia::render('Admin/FrontMenuSetting/Form', [
            'data' => $data,
            'isEdit' => true,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * 更新儲存
     */
    public function update(FrontMenuRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return redirect()->route('admin.front-menu-settings.index')
            ->with('result', $result);
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $result = $this->service->destroy($id);

        return redirect()->route('admin.front-menu-settings.index')
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

    // =============================================
    // API JSON（供 Modal 彈窗 CRUD 使用）
    // =============================================

    /**
     * API: 取得樹狀選單列表
     */
    public function apiList()
    {
        $tree = $this->service->getTreeList();

        return response()->json([
            'status' => true,
            'data' => $tree,
        ]);
    }

    /**
     * API: 取得單筆資料
     */
    public function apiShow($id)
    {
        $data = $this->service->getFormData($id);
        $parentOptions = $this->service->getParentOptions($id);

        return response()->json([
            'status' => true,
            'data' => $data,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * API: 新增
     */
    public function apiStore(FrontMenuRequest $request)
    {
        $result = $this->service->store($request->validated());

        return response()->json($result);
    }

    /**
     * API: 更新
     */
    public function apiUpdate(FrontMenuRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return response()->json($result);
    }

    /**
     * API: 取得刪除資訊
     */
    public function apiDeleteInfo($id)
    {
        $result = $this->service->getDeleteInfo($id);
        return response()->json($result);
    }

    /**
     * API: 刪除
     */
    public function apiDestroy($id)
    {
        $result = $this->service->destroy($id);

        return response()->json($result);
    }

    /**
     * API: 取得父層選項
     */
    public function apiParentOptions(Request $request)
    {
        $excludeId = $request->query('exclude_id');
        $options = $this->service->getParentOptions($excludeId);

        return response()->json([
            'status' => true,
            'data' => $options,
        ]);
    }

    /**
     * API: 取得前台用樹狀選單
     */
    public function apiFrontendTree(Request $request)
    {
        $locale = $request->query('locale', 'zh_TW');
        $tree = $this->service->getFrontendTree($locale);

        return response()->json([
            'status' => true,
            'data' => $tree,
        ]);
    }
}
