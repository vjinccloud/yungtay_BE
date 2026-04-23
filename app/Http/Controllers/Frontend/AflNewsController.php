<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * AFL 前台 - 最新消息 Controller
 */
class AflNewsController extends Controller
{
    /**
     * 最新消息列表頁
     */
    public function index(Request $request)
    {
        // 取得所有啟用的最新消息分類
        $categories = Category::where('type', 'news')
            ->where('status', true)
            ->orderBy('seq')
            ->get();

        // 取得至頂文章（用於 Banner 輪播）
        $pinnedNews = News::where('is_active', true)
            ->where('is_pinned', true)
            ->orderByDesc('published_date')
            ->limit(5)
            ->get();

        // 建構新聞列表查詢
        $query = News::where('is_active', true);

        // 分類篩選
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // 排序：置頂優先，然後按發布日期
        $newsList = $query->orderByDesc('is_pinned')
            ->orderByDesc('published_date')
            ->paginate(12)
            ->withQueryString();

        return view('frontend.afl.news.index', compact(
            'categories',
            'pinnedNews',
            'newsList'
        ));
    }

    /**
     * 最新消息詳細頁
     */
    public function show(Request $request, $id)
    {
        // 取得指定新聞
        $news = News::where('is_active', true)->findOrFail($id);

        // 取得分類參數（從 URL 帶入，若無則不限分類）
        $categoryId = $request->query('category');

        // 取得最新文章（固定抓最新 6 則，排除當前文章）
        $latestNews = News::where('is_active', true)
            ->where('id', '!=', $id)
            ->orderByDesc('published_date')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        // 建立基礎查詢（根據是否有分類篩選）
        $baseQuery = News::where('is_active', true);
        if ($categoryId) {
            $baseQuery->where('category_id', $categoryId);
        }

        // 取得上一篇（較新的文章：日期較晚，或日期相同但 ID 較大）
        $prevNews = (clone $baseQuery)
            ->where(function ($query) use ($news) {
                $query->where('published_date', '>', $news->published_date)
                    ->orWhere(function ($q) use ($news) {
                        $q->where('published_date', '=', $news->published_date)
                          ->where('id', '>', $news->id);
                    });
            })
            ->orderBy('published_date')
            ->orderBy('id')
            ->first();

        // 取得下一篇（較舊的文章：日期較早，或日期相同但 ID 較小）
        $nextNews = (clone $baseQuery)
            ->where(function ($query) use ($news) {
                $query->where('published_date', '<', $news->published_date)
                    ->orWhere(function ($q) use ($news) {
                        $q->where('published_date', '=', $news->published_date)
                          ->where('id', '<', $news->id);
                    });
            })
            ->orderByDesc('published_date')
            ->orderByDesc('id')
            ->first();

        return view('frontend.afl.news.show', compact(
            'news',
            'latestNews',
            'prevNews',
            'nextNews',
            'categoryId'
        ));
    }
}
