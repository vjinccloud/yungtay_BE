<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\ExpertArticle;
use Illuminate\Http\Request;

/**
 * AFL 前台 - 生命故事（專家文章）Controller
 */
class AflStoryController extends Controller
{
    /**
     * 專家文章列表頁（某位專家的所有文章）
     */
    public function index(Request $request, $expertId)
    {
        // 取得專家資料
        $expert = Expert::where('is_active', true)->findOrFail($expertId);

        // 取得該專家的文章列表
        $articles = ExpertArticle::where('is_active', true)
            ->where('expert_id', $expertId)
            ->orderByDesc('published_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('frontend.afl.story.index', compact(
            'expert',
            'articles'
        ));
    }

    /**
     * 專家文章詳細頁
     */
    public function show(Request $request, $expertId, $articleId)
    {
        // 取得專家資料
        $expert = Expert::where('is_active', true)->findOrFail($expertId);

        // 取得文章詳細
        $article = ExpertArticle::where('is_active', true)
            ->where('expert_id', $expertId)
            ->findOrFail($articleId);

        // 取得該專家的其他文章（排除當前文章，最多 9 篇）
        $relatedArticles = ExpertArticle::where('is_active', true)
            ->where('expert_id', $expertId)
            ->where('id', '!=', $articleId)
            ->orderByDesc('published_date')
            ->orderByDesc('id')
            ->limit(9)
            ->get();

        // 上一篇（較新的文章）
        $prevArticle = ExpertArticle::where('is_active', true)
            ->where('expert_id', $expertId)
            ->where(function ($query) use ($article) {
                $query->where('published_date', '>', $article->published_date)
                    ->orWhere(function ($q) use ($article) {
                        $q->where('published_date', '=', $article->published_date)
                          ->where('id', '>', $article->id);
                    });
            })
            ->orderBy('published_date')
            ->orderBy('id')
            ->first();

        // 下一篇（較舊的文章）
        $nextArticle = ExpertArticle::where('is_active', true)
            ->where('expert_id', $expertId)
            ->where(function ($query) use ($article) {
                $query->where('published_date', '<', $article->published_date)
                    ->orWhere(function ($q) use ($article) {
                        $q->where('published_date', '=', $article->published_date)
                          ->where('id', '<', $article->id);
                    });
            })
            ->orderByDesc('published_date')
            ->orderByDesc('id')
            ->first();

        return view('frontend.afl.story.show', compact(
            'expert',
            'article',
            'relatedArticles',
            'prevArticle',
            'nextArticle'
        ));
    }
}
