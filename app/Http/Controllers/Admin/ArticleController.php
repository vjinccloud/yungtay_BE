<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Services\ArticleService;
use App\Models\Article;
use App\Http\Requests\Article\ArticleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private ArticleService $articleService
    ) {
    }
    public function index(Request $request)
    {
        // 接收搜尋參數
        $filters = $request->only([
            'search',
            'category_id',
            'published_start_date',
            'published_end_date',
            'is_active'
        ]);

        // 處理搜尋參數（從 search_params 中取得）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');
            $filters = array_merge($filters, $searchParams);
        }

        $sortColumn = $request->input('sortColumn') ?? 'updated_at';
        $sortDirection = $request->input('sortDirection') ?? 'desc';
        $perPage = $request->input('length') ?? 10;

        $articles = $this->articleService->paginate($perPage, $sortColumn, $sortDirection, $filters);

        // 取得分類資料給搜尋表單使用
        $categories = $this->categoryService->getArticleCategories();

        return Inertia::render('Admin/Article/Index', [
            'articles' => $articles,
            'categories' => $categories['main'] ?? collect([])
        ]);
    }

    public function create()
    {
        $categories = $this->categoryService->getArticleCategories();
        
        return Inertia::render('Admin/Article/Form', [
            'categories' => $categories['main'] ?? collect([])
        ]);
    }

    public function edit($id)
    {
        $categories = $this->categoryService->getArticleCategories();
        $articleData = $this->articleService->getFormData($id);
        
        return Inertia::render('Admin/Article/Form', [
            'article' => $articleData,
            'categories' => $categories['main'] ?? collect([])
        ]);
    }

    public function store(ArticleRequest $request)
    {
        $result = $this->articleService->save($request->validated());
        return redirect()->back()->with('result', $result);
    }

    public function show(Article $article)
    {
        // TODO: 實作顯示邏輯
        return Inertia::render('Admin/Article/Show', [
            'article' => $article
        ]);
    }

    public function update(ArticleRequest $request, $id)
    {
        $result = $this->articleService->save($request->validated(), $id);
        return redirect()->back()->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->articleService->delete($id);
        return redirect()->back()->with('result', $result);
    }

    public function toggleActive(Request $request)
    {
        // 驗證請求
        $validated = $request->validate([
            'id' => 'required|exists:articles,id',
        ]);

        $result = $this->articleService->checkedStatus($validated['id']);
        return redirect()
        ->back()
        ->with('result', $result);
    }
}
