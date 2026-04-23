<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ExpertArticleService;
use App\Services\ExpertService;
use App\Http\Requests\Expert\ExpertArticleRequest;

class ExpertArticleController extends Controller
{
    public function __construct(
        private ExpertArticleService $articleService,
        private ExpertService $expertService,
    ) {}

    /**
     * 列表頁
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'expert_id', 'is_active']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order';
        $sortDirection = $request->input('sortDirection') ?? 'asc';
        $perPage = $request->input('length') ?? 15;

        $articles = $this->articleService->paginate($perPage, $sortColumn, $sortDirection, $filters);
        $experts = $this->expertService->getActiveExperts();

        return Inertia::render('Admin/ExpertArticles/Index', [
            'articles' => $articles,
            'experts' => $experts,
        ]);
    }

    /**
     * 新增頁
     */
    public function create()
    {
        $experts = $this->expertService->getActiveExperts();

        return Inertia::render('Admin/ExpertArticles/Form', [
            'experts' => $experts,
        ]);
    }

    /**
     * 儲存
     */
    public function store(ExpertArticleRequest $request)
    {
        $validated = $request->validated();
        $result = $this->articleService->save($validated);
        $result['redirect'] = route('admin.expert-articles');

        return redirect()->back()->with('result', $result);
    }

    /**
     * 編輯頁
     */
    public function edit(string $id)
    {
        $articleData = $this->articleService->getFormData($id);
        $experts = $this->expertService->getActiveExperts();

        return Inertia::render('Admin/ExpertArticles/Form', [
            'article' => $articleData,
            'experts' => $experts,
        ]);
    }

    /**
     * 更新
     */
    public function update(ExpertArticleRequest $request, string $id)
    {
        $validated = $request->validated();
        $result = $this->articleService->save($validated, $id);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $result = $this->articleService->delete($id);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:expert_articles,id',
        ]);

        $result = $this->articleService->checkedStatus($validated['id']);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 更新排序
     */
    public function sort(Request $request)
    {
        $validated = $request->validate([
            'sorted_ids' => 'required|array',
            'sorted_ids.*' => 'exists:expert_articles,id',
        ]);

        $result = $this->articleService->updateSort($validated['sorted_ids']);

        return redirect()->back()->with('result', $result);
    }
}
