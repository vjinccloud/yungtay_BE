<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * SearchController (Frontend)
 * 
 * 處理前台搜尋頁面顯示
 */
class SearchController extends Controller
{
    /**
     * 顯示搜尋結果頁面
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $keyword = $request->input('keyword', '');

        // 傳遞資料到 Blade 模板
        $data = [
            'keyword' => $keyword,
            'hasKeyword' => !empty(trim($keyword))
        ];

        return view('frontend.search.result', $data);
    }
}