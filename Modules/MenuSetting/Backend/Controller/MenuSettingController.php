<?php

namespace Modules\MenuSetting\Backend\Controller;

use App\Http\Controllers\Controller;
use App\Models\AdminMenu;
use Modules\MenuSetting\Backend\Request\MenuSettingRequest;
use Modules\MenuSetting\Backend\Service\MenuSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuSettingController extends Controller
{
    protected MenuSettingService $service;

    public function __construct(MenuSettingService $service)
    {
        $this->service = $service;
    }

    // =============================================
    // Inertia 頁面（完整頁面 CRUD）
    // =============================================

    /**
     * 列表頁
     */
    public function index(Request $request)
    {
        $list = $this->service->getList($request);
        $parentOptions = $this->service->getParentOptions();

        return Inertia::render('Admin/MenuSetting/Index', [
            'list' => $list,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * 新增頁面
     */
    public function create()
    {
        $parentOptions = $this->service->getParentOptions();

        return Inertia::render('Admin/MenuSetting/Form', [
            'data' => null,
            'isEdit' => false,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * 新增儲存
     */
    public function store(MenuSettingRequest $request)
    {
        $result = $this->service->store($request->validated());
        $result['redirect'] = route('admin.menu-settings.index');

        return Inertia::render('Admin/MenuSetting/Form', [
            'result' => $result,
        ]);
    }

    /**
     * 編輯頁面
     */
    public function edit($id)
    {
        $data = $this->service->getFormData($id);
        $parentOptions = $this->service->getParentOptions($id);

        return Inertia::render('Admin/MenuSetting/Form', [
            'data' => $data,
            'isEdit' => true,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * 更新儲存
     */
    public function update(MenuSettingRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());
        $result['redirect'] = route('admin.menu-settings.index');

        return Inertia::render('Admin/MenuSetting/Form', [
            'result' => $result,
            'data' => $this->service->getFormData($id),
            'isEdit' => true,
            'parentOptions' => $this->service->getParentOptions($id),
        ]);
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $result = $this->service->destroy($id);

        return redirect()->route('admin.menu-settings.index')
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
    public function apiStore(MenuSettingRequest $request)
    {
        $result = $this->service->store($request->validated());

        return response()->json($result);
    }

    /**
     * API: 更新
     */
    public function apiUpdate(MenuSettingRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return response()->json($result);
    }

    /**
     * API: 刪除
     */
    /**
     * API - 取得刪除資訊（含子孫數量，供前端警告用）
     */
    public function apiDeleteInfo($id)
    {
        $result = $this->service->getDeleteInfo($id);
        return response()->json($result);
    }

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
        $excludeId = $request->input('exclude_id');
        $options = $this->service->getParentOptions($excludeId);

        return response()->json([
            'status' => true,
            'data' => $options,
        ]);
    }
}
