<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\News\NewsRequest;
use App\Services\NewsService;
use App\Services\CategoryService;

class NewsController extends Controller
{
    public function __construct(
        private NewsService $news,
        private CategoryService $categoryService,
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'updated_at'; // 默認排序列為 `id`
        $sortDirection = $request->input('sortDirection') ?? 'desc'; // 默認排序方向為升序
        $perPage = $request->input('length') ?? '1';

        $news = $this->news->paginate($perPage, $sortColumn, $sortDirection, $filters);
        return Inertia::render('Admin/News/Index', compact('news'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = $this->categoryService->getCategoriesByType('news');
        return Inertia::render('Admin/News/Form', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NewsRequest $request)
    {
        $validated = $request->validated();
        $result = $this->news->save($validated);
        $result['redirect'] = route('admin.news');
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $newsData = $this->news->getFormData($id);
        $categories = $this->categoryService->getCategoriesByType('news');
        return Inertia::render('Admin/News/Form', [
            'news' => $newsData,
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NewsRequest $request, string $id)
    {
        $validated = $request->validated();
        $result = $this->news->save($validated, $id);
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $result = $this->news->delete($id);
        return redirect()
            ->back()
            ->with('result', $result);
    }


    public function toggleActive(Request $request)
    {
        // 驗證請求
        $validated = $request->validate([
            'id' => 'required|exists:news,id',
        ]);

        $result = $this->news->checkedStatus($validated['id']);
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 切換首頁曝光文章狀態
     */
    public function toggleHomepageFeatured(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:news,id',
        ]);

        $result = $this->news->toggleHomepageFeatured($validated['id']);
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 切換置頂文章狀態
     */
    public function togglePinned(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:news,id',
        ]);

        $result = $this->news->togglePinned($validated['id']);
        return redirect()
            ->back()
            ->with('result', $result);
    }
}
