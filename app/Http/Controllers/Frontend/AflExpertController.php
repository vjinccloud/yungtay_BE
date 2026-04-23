<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\ExpertCategory;
use Illuminate\Http\Request;

/**
 * AFL 前台 - 生命故事（專家）Controller
 */
class AflExpertController extends Controller
{
    /**
     * 生命故事列表頁
     */
    public function index(Request $request)
    {
        // 取得所有啟用的專家分類
        $categories = ExpertCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // 取得首席專家（is_featured = true，只取第一位）
        $featuredExpert = Expert::where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->first();

        // 取得熱門專家（非首席，最多 3 位，sort_order 最前面的）
        $hotExpertsQuery = Expert::where('is_active', true)
            ->where('is_featured', false)
            ->orderBy('sort_order')
            ->limit(3);
        
        // 如果有首席專家，排除首席
        if ($featuredExpert) {
            $hotExpertsQuery->where('id', '!=', $featuredExpert->id);
        }
        $hotExperts = $hotExpertsQuery->get();

        // 建構專家列表查詢
        $query = Expert::where('is_active', true);

        // 分類篩選
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // 排序：sort_order 優先
        $expertList = $query->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('frontend.afl.expert.index', compact(
            'categories',
            'featuredExpert',
            'hotExperts',
            'expertList'
        ));
    }

    /**
     * 生命故事詳細頁
     */
    public function show(Request $request, $id)
    {
        // 取得指定專家
        $expert = Expert::where('is_active', true)->findOrFail($id);

        // 取得分類參數
        $categoryId = $request->query('category');

        // 取得最新專家（排除當前專家，最多 6 位）
        $latestExperts = Expert::where('is_active', true)
            ->where('id', '!=', $id)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        // 建立基礎查詢（根據是否有分類篩選）
        $baseQuery = Expert::where('is_active', true);
        if ($categoryId) {
            $baseQuery->where('category_id', $categoryId);
        }

        // 取得上一位（sort_order 較小的）
        $prevExpert = (clone $baseQuery)
            ->where(function ($query) use ($expert) {
                $query->where('sort_order', '<', $expert->sort_order)
                    ->orWhere(function ($q) use ($expert) {
                        $q->where('sort_order', '=', $expert->sort_order)
                          ->where('id', '<', $expert->id);
                    });
            })
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->first();

        // 取得下一位（sort_order 較大的）
        $nextExpert = (clone $baseQuery)
            ->where(function ($query) use ($expert) {
                $query->where('sort_order', '>', $expert->sort_order)
                    ->orWhere(function ($q) use ($expert) {
                        $q->where('sort_order', '=', $expert->sort_order)
                          ->where('id', '>', $expert->id);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        return view('frontend.afl.expert.show', compact(
            'expert',
            'latestExperts',
            'prevExpert',
            'nextExpert',
            'categoryId'
        ));
    }
}
