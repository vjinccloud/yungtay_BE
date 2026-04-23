<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DramaService;

class ApiDramaController extends Controller
{
    public function __construct(
        private DramaService $dramaService
    ) {}

    /**
     * 影音篩選 API
     */
    public function filter(Request $request)
    {
        // 準備篩選參數
        $filters = [
            'category_id' => $request->get('category_id'),
            'subcategories' => $request->get('subcategories', []),
            'years' => $request->get('years', [])
        ];
        
        // 每頁顯示數量
        $perPage = $request->get('per_page', 20);
        
        // 呼叫 Service 取得篩選結果
        $result = $this->dramaService->getFilteredDramas($filters, $perPage);
        
        return response()->json($result);
    }

    /**
     * 取得影音詳情
     */
    public function show($id)
    {
        // TODO: 實作影音詳情 API
        return response()->json([
            'success' => true,
            'data' => null
        ]);
    }

    /**
     * 取得影音的影片列表
     */
    public function episodes($dramaId)
    {
        // TODO: 實作影片列表 API
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }
}