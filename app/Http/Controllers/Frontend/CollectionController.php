<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\CollectionService;
use App\Http\Requests\Collection\CollectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectionController extends Controller
{
    protected $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * 顯示收藏列表頁面
     */
    public function index()
    {
        return view('frontend.member.collection', [
            'pageTitle' => __('frontend.member.collection'),
        ]);
    }

    /**
     * 新增收藏
     */
    public function add(CollectionRequest $request)
    {
        $validated = $request->validated();
        $data = [
            'user_id' => Auth::id(),
            'content_type' => $validated['content_type'],
            'content_id' => $validated['content_id'],
        ];

        $result = $this->collectionService->addCollection($data);

        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ? 200 : 400);
        }

        return redirect()->back()->with('result', $result);
    }

    /**
     * 移除收藏
     */
    public function remove(CollectionRequest $request)
    {
        $validated = $request->validated();
        $data = [
            'user_id' => Auth::id(),
            'content_type' => $validated['content_type'],
            'content_id' => $validated['content_id'],
        ];

        $result = $this->collectionService->removeCollection($data);

        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ? 200 : 400);
        }

        return redirect()->back()->with('result', $result);
    }

    /**
     * 取得收藏列表資料 (AJAX)
     */
    public function getData(Request $request)
    {
        $contentType = $request->get('content_type', 'articles'); // 預設新聞
        
        // 取得全部收藏（不分頁）
        $result = $this->collectionService->getUserAllCollections(Auth::id(), $contentType);

        return response()->json($result);
    }

    /**
     * 批次檢查收藏狀態
     */
    public function checkStatus(Request $request)
    {
        $contentType = $request->get('content_type');
        $contentIds = $request->get('content_ids', []);

        $result = $this->collectionService->batchCheckCollected(Auth::id(), $contentType, $contentIds);

        return response()->json($result);
    }
}
