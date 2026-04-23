<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ExpertService;
use App\Services\ExpertCategoryService;
use App\Http\Requests\Expert\ExpertRequest;

class ExpertController extends Controller
{
    public function __construct(
        private ExpertService $expertService,
        private ExpertCategoryService $categoryService,
    ) {}

    /**
     * 列表頁
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id', 'is_active']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order';
        $sortDirection = $request->input('sortDirection') ?? 'asc';
        $perPage = $request->input('length') ?? 15;

        $experts = $this->expertService->paginate($perPage, $sortColumn, $sortDirection, $filters);
        $categories = $this->categoryService->getActiveCategories();

        return Inertia::render('Admin/Experts/Index', [
            'experts' => $experts,
            'categories' => $categories,
        ]);
    }

    /**
     * 新增頁
     */
    public function create()
    {
        $categories = $this->categoryService->getActiveCategories();

        return Inertia::render('Admin/Experts/Form', [
            'categories' => $categories,
        ]);
    }

    /**
     * 儲存
     */
    public function store(ExpertRequest $request)
    {
        $validated = $request->validated();
        $result = $this->expertService->save($validated);
        $result['redirect'] = route('admin.experts');

        return redirect()->back()->with('result', $result);
    }

    /**
     * 編輯頁
     */
    public function edit(string $id)
    {
        $expertData = $this->expertService->getFormData($id);
        $categories = $this->categoryService->getActiveCategories();

        return Inertia::render('Admin/Experts/Form', [
            'expert' => $expertData,
            'categories' => $categories,
        ]);
    }

    /**
     * 更新
     */
    public function update(ExpertRequest $request, string $id)
    {
        $validated = $request->validated();
        $result = $this->expertService->save($validated, $id);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $result = $this->expertService->delete($id);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:experts,id',
        ]);

        $result = $this->expertService->checkedStatus($validated['id']);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換主打狀態（首席專家只能有一個）
     */
    public function toggleFeatured(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:experts,id',
        ]);

        $expert = $this->expertService->find($validated['id']);
        $newFeaturedStatus = !$expert->is_featured;

        // 如果要設為首席專家，先取消其他人的首席狀態
        if ($newFeaturedStatus) {
            \App\Models\Expert::where('is_featured', true)
                ->where('id', '!=', $validated['id'])
                ->update(['is_featured' => false]);
        }

        $result = $this->expertService->save([
            'is_featured' => $newFeaturedStatus,
        ], $validated['id']);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 更新排序
     */
    public function sort(Request $request)
    {
        $validated = $request->validate([
            'sorted_ids' => 'required|array',
            'sorted_ids.*' => 'exists:experts,id',
        ]);

        $result = $this->expertService->updateSort($validated['sorted_ids']);

        return redirect()->back()->with('result', $result);
    }
}
