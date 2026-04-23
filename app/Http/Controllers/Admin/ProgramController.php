<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\CategoryService;
use App\Services\ProgramEpisodeService;
use App\Services\ProgramService;
use App\Services\ViewService;
use App\Http\Requests\Program\ProgramRequest;

class ProgramController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private ProgramEpisodeService $programEpisodeService,
        private ProgramService $programService,
        private ViewService $viewService
    ) {
    }

    /**
     * 顯示節目列表
     */
    public function index(Request $request)
    {
        // 接收搜尋參數
        $filters = $request->only([
            'search',
            'category_id',
            'subcategory_id',
            'published_start_date',
            'published_end_date',
            'release_year',
            'is_active'
        ]);

        // 處理搜尋參數（從 search_params 中取得）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');
            $filters = array_merge($filters, $searchParams);
        }

        $sortColumn = $request->input('sortColumn') ?? 'updated_at';
        $sortDirection = $request->input('sortDirection') ?? 'desc';
        $perPage = $request->input('length') ?? 10;

        $programs = $this->programService->getDataTableData($perPage, $sortColumn, $sortDirection, $filters);

        // 取得分類資料給搜尋表單使用
        $categories = $this->categoryService->getProgramCategories();

        return Inertia::render('Admin/Programs/Index', [
            'programs' => $programs,
            'categories' => $categories['main'],
            'subcategories' => $categories['sub']
        ]);
    }

    public function create()
    {
        $categories = $this->categoryService->getProgramCategories();
        $videoSeasons = $this->programEpisodeService->getVideoSeasons();
        return Inertia::render('Admin/Programs/Form', [
            'title' => '新增節目',
            'program' => null,
            'categories' => $categories['main'],
            'subcategories' => $categories['sub'],
            'videoSeasons' => $videoSeasons
        ]);
    }

    public function store(ProgramRequest $request)
    {
        $validated = $request->validated();
        $result = $this->programService->save($validated);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function show($id)
    {
        return redirect()->route('admin.programs.edit', $id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            // 取得編輯所需的完整資料
            $editData = $this->programService->getEditData($id);
            return Inertia::render('Admin/Programs/Form', [
                'program' => $editData['program'],
                'categories' => $editData['categories'],
                'subcategories' => $editData['subcategories'],
                'videoSeasons' => $editData['videoSeasons'],
            ]);

        } catch (\Exception $e) {
            // 如果節目不存在或其他錯誤，重導向到列表頁面
            return redirect()
                ->route('admin.programs')
                ->with('result', [
                    'status' => false,
                    'msg' => '節目資料不存在或載入失敗：' . $e->getMessage()
                ]);
        }
    }

    public function update(ProgramRequest $request, $id)
    {
        $validated = $request->validated();
        $result = $this->programService->save($validated, $id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->programService->delete($id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function toggleActive(Request $request)
    {
        // 驗證請求
        $validated = $request->validate([
            'id' => 'required|exists:programs,id',
        ]);

        $result = $this->programService->checkedStatus($validated['id']);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
     * 顯示節目觀看紀錄統計頁面
     */
    public function viewLogs($id)
    {
        try {
            // 取得節目基本資料
            $programData = $this->programService->getEditData($id);
            $program = $programData['program'];

            // 取得觀看統計數據
            $statistics = $this->viewService->getContentViewStatistics('program', $id);

            // 取得所有可用的季數
            $availableSeasons = $this->programEpisodeService->getVideoSeasons($id);

            return Inertia::render('Admin/Shared/Content/ViewLogs', [
                'contentType' => 'program',
                'contentId' => (int) $id,
                'contentTitle' => data_get($program, 'title.zh_TW') ?? '未命名節目',
                'statistics' => $statistics,
                'availableSeasons' => $availableSeasons,
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.programs')
                ->with('result', [
                    'status' => false,
                    'msg' => '載入觀看紀錄失敗：' . $e->getMessage()
                ]);
        }
    }

    /**
     * 取得節目集數觀看紀錄資料（AJAX）
     */
    public function viewLogsData(Request $request, $id)
    {
        try {
            $filters = $request->all();
            $result = $this->viewService->getEpisodeViewLogs('program', $id, $filters);

            // 轉換為 DataTableHelper AJAX 模式期望的格式
            return response()->json([
                'data' => $result['data'],
                'total' => $result['recordsTotal'] ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
