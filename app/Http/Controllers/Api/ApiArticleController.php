<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ArticleService;
use Illuminate\Http\Request;

class ApiArticleController extends Controller
{
    public function __construct(
        private ArticleService $articleService
    ) {}

    /**
     * 取得文章列表（支援分頁和分類篩選）
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 20);
            $categoryId = $request->get('category_id');
            
            $articles = $this->articleService->getFrontendList($perPage, $categoryId);
            
            return response()->json([
                'success' => true,
                'data' => $articles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '取得文章失敗'
            ], 500);
        }
    }

    /**
     * 取得分類文章
     */
    public function categoryArticles($categoryId)
    {
        try {
            $articles = $this->articleService->getFrontendArticles(8, $categoryId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'articles' => $articles
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '取得文章失敗'
            ], 500);
        }
    }
}