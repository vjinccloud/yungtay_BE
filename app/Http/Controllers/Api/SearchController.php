<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Services\SearchService;

/**
 * SearchController (API)
 * 
 * 處理全站搜尋的 AJAX 請求
 * 遵循 MSR 架構：Controller → Service → Repository
 */
class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }


    /**
     * 搜尋單一類別
     * 
     * @param SearchRequest $request
     * @param string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByType(SearchRequest $request, $type)
    {
        // 驗證類別類型
        if (!in_array($type, ['article', 'drama', 'program', 'live', 'radio', 'news'])) {
            return response()->json([
                'status' => false,
                'msg' => '不支援的搜尋類別'
            ], 422);
        }

        $keyword = $request->getKeyword(); // 已經過 XSS 防護處理
        $page = $request->input('page', 1);
        $mode = $request->input('mode', 'single'); // all 或 single
        $perPage = $request->input('per_page'); // 前端傳來的每頁數量

        $result = $this->searchService->searchByType($type, $keyword, $page, $mode, $perPage);

        return response()->json($result, $result['status'] ? 200 : 422);
    }
}