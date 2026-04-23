<?php

namespace Modules\ProductSpecSetting\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\ProductSpecSetting\Backend\Request\SpecGroupRequest;
use Modules\ProductSpecSetting\Backend\Request\SpecValueRequest;
use Modules\ProductSpecSetting\Backend\Service\ProductSpecService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductSpecController extends Controller
{
    protected ProductSpecService $service;

    public function __construct(ProductSpecService $service)
    {
        $this->service = $service;
    }

    // ========================================
    // Inertia Pages
    // ========================================

    /**
     * 規格設定主頁（群組列表 + 組合列表）
     */
    public function index()
    {
        $groups = $this->service->getGroupList();
        $combinations = $this->service->getCombinationList();

        return Inertia::render('Admin/ProductSpecSetting/Index', [
            'groups' => $groups,
            'combinations' => $combinations,
        ]);
    }

    /**
     * 新增規格群組頁面
     */
    public function createGroup()
    {
        return Inertia::render('Admin/ProductSpecSetting/GroupForm', [
            'data' => null,
            'isEdit' => false,
        ]);
    }

    /**
     * 儲存新規格群組
     */
    public function storeGroup(SpecGroupRequest $request)
    {
        $result = $this->service->storeGroup($request->validated());
        return redirect()->route('admin.product-spec-settings.index')
            ->with('result', $result);
    }

    /**
     * 編輯規格群組頁面
     */
    public function editGroup($id)
    {
        $data = $this->service->getGroupFormData($id);
        return Inertia::render('Admin/ProductSpecSetting/GroupForm', [
            'data' => $data,
            'isEdit' => true,
        ]);
    }

    /**
     * 更新規格群組
     */
    public function updateGroup(SpecGroupRequest $request, $id)
    {
        $result = $this->service->updateGroup($id, $request->validated());
        return redirect()->route('admin.product-spec-settings.index')
            ->with('result', $result);
    }

    /**
     * 刪除規格群組
     */
    public function destroyGroup($id)
    {
        $result = $this->service->destroyGroup($id);
        return redirect()->route('admin.product-spec-settings.index')
            ->with('result', $result);
    }

    /**
     * 切換群組啟用狀態
     */
    public function toggleGroupActive(Request $request)
    {
        $result = $this->service->toggleGroupActive($request->id);
        return response()->json($result);
    }

    /**
     * 更新群組排序
     */
    public function updateGroupSort(Request $request)
    {
        $result = $this->service->updateGroupSort($request->items);
        return response()->json($result);
    }

    /**
     * 切換組合啟用狀態
     */
    public function toggleCombinationActive(Request $request)
    {
        $result = $this->service->toggleCombinationActive($request->id);
        return response()->json($result);
    }

    // ========================================
    // API JSON (for Modal / AJAX)
    // ========================================

    // --- 群組 ---

    public function apiGroupList()
    {
        $groups = $this->service->getGroupList();
        return response()->json(['status' => true, 'data' => $groups]);
    }

    public function apiGroupShow($id)
    {
        $data = $this->service->getGroupFormData($id);
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function apiGroupStore(SpecGroupRequest $request)
    {
        $result = $this->service->storeGroup($request->validated());
        return response()->json($result);
    }

    public function apiGroupUpdate(SpecGroupRequest $request, $id)
    {
        $result = $this->service->updateGroup($id, $request->validated());
        return response()->json($result);
    }

    public function apiGroupDestroy($id)
    {
        $result = $this->service->destroyGroup($id);
        return response()->json($result);
    }

    // --- 規格值 ---

    public function apiValueStore(SpecValueRequest $request, $groupId)
    {
        $result = $this->service->storeValue($groupId, $request->validated());
        return response()->json($result);
    }

    public function apiValueUpdate(SpecValueRequest $request, $id)
    {
        $result = $this->service->updateValue($id, $request->validated());
        return response()->json($result);
    }

    public function apiValueDestroy($id)
    {
        $result = $this->service->destroyValue($id);
        return response()->json($result);
    }

    public function apiValueToggleActive(Request $request)
    {
        $result = $this->service->toggleValueActive($request->id);
        return response()->json($result);
    }

    // --- 規格組合 ---

    public function apiCombinationList()
    {
        $combinations = $this->service->getCombinationList();
        return response()->json(['status' => true, 'data' => $combinations]);
    }

    public function apiCombinationShow($id)
    {
        $data = $this->service->getCombinationFormData($id);
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function apiStoreCombination(Request $request)
    {
        $result = $this->service->storeCombination($request->all());
        return response()->json($result);
    }

    public function apiCombinationUpdate(Request $request, $id)
    {
        $result = $this->service->updateCombination($id, $request->all());
        return response()->json($result);
    }

    public function apiCombinationDestroy($id)
    {
        $result = $this->service->destroyCombination($id);
        return response()->json($result);
    }

    // --- 前台結構 ---

    public function apiSpecStructure(Request $request)
    {
        $locale = $request->query('locale', 'zh_TW');
        $structure = $this->service->getSpecStructure($locale);
        return response()->json(['status' => true, 'data' => $structure]);
    }
}
