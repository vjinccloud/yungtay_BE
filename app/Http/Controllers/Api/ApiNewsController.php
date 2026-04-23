<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\NewsService;
class ApiNewsController extends Controller
{
    public function __construct(
        private NewsService $newsService,
    )
    {

    }
    /**
     * 取得最新消息列表
     */
    public function index(Request $request)
    {
        $news = $this->newsService->getFrontendList(
            $request->input('per_page', 6),
            $request->input('search')
        );
        
        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * 取得單一最新消息詳情
     */
    public function show($id)
    {
        // TODO: 實作最新消息詳情查詢
        return response()->json([
            'success' => true,
            'data' => null
        ]);
    }
}