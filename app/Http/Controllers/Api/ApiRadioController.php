<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RadioService;

class ApiRadioController extends Controller
{
    public function __construct(
        private RadioService $radioService,
    )
    {
    }

    /**
     * 取得廣播列表
     */
    public function index(Request $request)
    {
        $radios = $this->radioService->getFrontendList(
            $request->input('per_page', 1),
            $request->input('category_id')
        );
        return response()->json([
            'success' => true,
            'data' => $radios
        ]);
    }

    /**
     * 取得單一廣播詳情
     */
    public function show($id)
    {
        $radio = $this->radioService->getRadioDetail($id);

        return response()->json([
            'success' => true,
            'data' => $radio
        ]);
    }

    /**
     * 廣播篩選 API
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
        $result = $this->radioService->getFilteredRadios($filters, $perPage);

        return response()->json($result);
    }
}