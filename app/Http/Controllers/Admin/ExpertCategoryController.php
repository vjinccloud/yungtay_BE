<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ExpertCategoryService;

class ExpertCategoryController extends Controller
{
    public function __construct(
        private ExpertCategoryService $categoryService,
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

        $categories = $this->categoryService->paginate($perPage, $sortColumn, $sortDirection, $filters);

        return Inertia::render('Admin/ExpertCategories/Index', compact('categories'));
    }

    /**
     * 新增頁
     */
    public function create()
    {
        return Inertia::render('Admin/ExpertCategories/Form');
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
            'name.zh_TW.required' => '請輸入分類名稱',
        ]);

        $result = $this->categoryService->save($validated);
        $result['redirect'] = route('admin.expert-categories');

        return redirect()->back()->with('result', $result);
    }

    /**
     * 編輯頁
     */
    public function edit(string $id)
    {
        $category = $this->categoryService->find($id);

        $categoryData = [
            'id' => $category->id,
            'name' => [
                'zh_TW' => $category->getTranslation('name', 'zh_TW'),
            ],
            'is_active' => (bool) $category->is_active,
        ];

        return Inertia::render('Admin/ExpertCategories/Form', [
            'category' => $categoryData,
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
            'name.zh_TW.required' => '請輸入分類名稱',
        ]);

        $result = $this->categoryService->save($validated, $id);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $result = $this->categoryService->delete($id);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:expert_categories,id',
        ]);

        $result = $this->categoryService->checkedStatus($validated['id']);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 更新排序
     */
    public function sort(Request $request)
    {
        $validated = $request->validate([
            'sorted_ids' => 'required|array',
            'sorted_ids.*' => 'exists:expert_categories,id',
        ]);

        $result = $this->categoryService->updateSort($validated['sorted_ids']);

        return redirect()->back()->with('result', $result);
    }
}
