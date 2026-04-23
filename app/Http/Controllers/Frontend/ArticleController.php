<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\ArticleService;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleService $articleService,
        private CategoryService $categoryService
    ) {}

    /**
     * 顯示新聞列表頁（hot_news_all_list）
     */
    public function index(Request $request)
    {
        // 取得最新4則文章（熱門新聞區塊）
        $latestArticles = $this->articleService->getFrontendArticles(4);

        // 讀取選擇的分類 ID（單一分類，預設為 0 = 所有新聞）
        $selectedCategoryId = (int) $request->get('category_id', 0);

        // category_id = 0 表示「全部分類」，轉為 null 給 Repository
        $categoryId = ($selectedCategoryId === 0) ? null : $selectedCategoryId;

        // 取得新聞列表（分頁）- Laravel 會自動從 URL 讀取 page 參數
        $articles = $this->articleService->getFrontendList(20, $categoryId);

        // 取得文章分類資料
        $categories = $this->categoryService->getArticleCategories(true);

        // 取得模組 SEO
        $moduleSEO = $this->articleService->getModuleSEO('article');

        // 預載第一頁的分頁資料（給 JSON-LD 和 Vue 組件）
        // 只有在第一頁時才使用當前資料，其他頁面重新查詢第一頁
        $firstPageArticles = $articles->currentPage() === 1 ? $articles : $this->articleService->getFrontendList(20, $categoryId);

        return view('frontend.articles.index', [
            'latestArticles' => $latestArticles,
            'articles' => $articles,  // 分頁資料（包含正確的當前頁）
            'firstPageArticles' => $firstPageArticles,  // JSON-LD 用（永遠是第一頁）
            'categories' => $categories['main'] ?? collect([]),
            'selectedCategoryId' => $selectedCategoryId,  // 傳遞選中的分類 ID
            'metaOverride' => $moduleSEO ?? []
        ]);
    }

    /**
     * 顯示新聞詳情頁（hot_news_detail）
     */
    public function show($id)
    {
        // 取得文章詳情（前台用，已處理語系）
        $article = $this->articleService->getFrontendDetail($id);
        
        if (!$article) {
            abort(404);
        }
        
        // 取得相關文章（同分類的其他文章，排除當前文章）
        $relatedArticles = [];
        if (isset($article['category_id']) && $article['category_id']) {
            $relatedArticles = $this->articleService->getFrontendArticles(4, $article['category_id'], $id);
        }
                
        // 取得詳情頁 SEO
        $metaOverride = $this->articleService->getDetailSEO($article);
        return view('frontend.articles.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'metaOverride' => $metaOverride
        ]);
    }
}