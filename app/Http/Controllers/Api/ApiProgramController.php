<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProgramService;

class ApiProgramController extends Controller
{
    public function __construct(
        private ProgramService $programService
    ) {}

    /**
     * 篩選節目
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
        $result = $this->programService->getFilteredPrograms($filters, $perPage);
        
        return response()->json($result);
    }

    /**
     * 取得單一節目資訊
     */
    public function show($id)
    {
        try {
            $program = $this->programService->find($id);
            
            if (!$program) {
                return response()->json([
                    'success' => false,
                    'message' => '找不到該節目'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'program' => $program
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '載入節目失敗'
            ], 500);
        }
    }

    /**
     * 取得節目的影片列表
     */
    public function episodes($programId)
    {
        // TODO: 實作影片列表 API
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }
}