<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ExpertFieldService;

class ExpertFieldController extends Controller
{
    public function __construct(
        private ExpertFieldService $fieldService,
    ) {}

    /**
     * 列表頁
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order';
        $sortDirection = $request->input('sortDirection') ?? 'asc';
        $perPage = $request->input('length') ?? 15;

        $fields = $this->fieldService->paginate($perPage, $sortColumn, $sortDirection, $filters);

        return Inertia::render('Admin/ExpertFields/Index', [
            'fields' => $fields,
        ]);
    }

    /**
     * 新增頁
     */
    public function create()
    {
        return Inertia::render('Admin/ExpertFields/Form');
    }

    /**
     * 儲存
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name.zh_TW' => 'required|string|max:255',
            'is_active' => 'boolean',
        ], [
            'name.zh_TW.required' => '請輸入領域名稱',
        ]);

        $result = $this->fieldService->save($validated);
        $result['redirect'] = route('admin.expert-fields');

        return redirect()->back()->with('result', $result);
    }

    /**
     * 編輯頁
     */
    public function edit(string $id)
    {
        $field = $this->fieldService->find($id);

        $fieldData = [
            'id' => $field->id,
            'name' => [
                'zh_TW' => $field->getTranslation('name', 'zh_TW'),
            ],
            'is_active' => (bool) $field->is_active,
        ];

        return Inertia::render('Admin/ExpertFields/Form', [
            'field' => $fieldData,
        ]);
    }

    /**
     * 更新
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name.zh_TW' => 'required|string|max:255',
            'is_active' => 'boolean',
        ], [
            'name.zh_TW.required' => '請輸入領域名稱',
        ]);

        $result = $this->fieldService->save($validated, $id);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $result = $this->fieldService->delete($id);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:expert_fields,id',
        ]);

        $result = $this->fieldService->checkedStatus($validated['id']);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 更新排序
     */
    public function sort(Request $request)
    {
        $validated = $request->validate([
            'sorted_ids' => 'required|array',
            'sorted_ids.*' => 'exists:expert_fields,id',
        ]);

        $result = $this->fieldService->updateSort($validated['sorted_ids']);

        return redirect()->back()->with('result', $result);
    }
}
