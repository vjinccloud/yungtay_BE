<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\CategoryService;
use App\Services\DramaEpisodeService;
use App\Services\DramaService;
use App\Http\Requests\Drama\DramaRequest;
use App\Services\ViewService;
class DramaController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private DramaEpisodeService $dramaEpisodeService,
        private DramaService $dramaService,
        private ViewService $viewService
    ) {
    }

    /**
     * 顯示影音列表
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

        $dramas = $this->dramaService->getDataTableData($perPage, $sortColumn, $sortDirection, $filters);

        // 取得分類資料給搜尋表單使用
        $categories = $this->categoryService->getDramaCategories();

        return Inertia::render('Admin/Dramas/Index', [
            'dramas' => $dramas,
            'categories' => $categories['main'],
            'subcategories' => $categories['sub']
        ]);
    }

    public function create()
    {
        $categories = $this->categoryService->getDramaCategories();
        $videoSeasons = $this->dramaEpisodeService->getVideoSeasons();
        return Inertia::render('Admin/Dramas/Form', [
            'title' => '新增影音',
            'drama' => null,
            'categories' => $categories['main'],
            'subcategories' => $categories['sub'],
            'videoSeasons' => $videoSeasons
        ]);
    }

    public function store(DramaRequest $request)
    {
        $validated = $request->validated();
        $result = $this->dramaService->save($validated);
        return redirect()
        ->back()
        ->with('result', $result);

    }

    public function show($id)
    {
        return redirect()->route('admin.dramas.edit', $id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            // 取得編輯所需的完整資料
            $editData = $this->dramaService->getEditData($id);


            return Inertia::render('Admin/Dramas/Form', [
                'drama' => $editData['drama'],
                'categories' => $editData['categories'],
                'subcategories' => $editData['subcategories'],
                'videoSeasons' => $editData['videoSeasons'],
            ]);

        } catch (\Exception $e) {
            // 如果影音不存在或其他錯誤，重導向到列表頁面
            return redirect()
                ->route('admin.dramas')
                ->with('result', [
                    'status' => false,
                    'msg' => '影音資料不存在或載入失敗：' . $e->getMessage()
                ]);
        }
    }

    public function update(DramaRequest $request, $id)
    {
        $validated = $request->validated();
        $result = $this->dramaService->save($validated , $id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->dramaService->delete($id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function toggleActive(Request $request)
    {
        // 驗證請求
        $validated = $request->validate([
            'id' => 'required|exists:dramas,id',
        ]);

        $result = $this->dramaService->checkedStatus($validated['id']);
        return redirect()
        ->back()
        ->with('result', $result);
    }

     /**
     * 顯示觀看紀錄統計頁面
     */
    public function viewLogs($id)
    {
        try {
            // 取得基本資料
            $dramaData = $this->dramaService->getEditData($id);
            $drama = $dramaData['drama'];

            // 取得觀看統計數據
            $statistics = $this->viewService->getContentViewStatistics('drama', $id);

            // 取得所有可用的季數
            $availableSeasons = $this->dramaEpisodeService->getVideoSeasons($id);
            return Inertia::render('Admin/Shared/Content/ViewLogs', [
                'contentType' => 'drama',
                'contentId' => (int) $id,
                'contentTitle' => data_get($drama, 'title.zh_TW') ?? '未命名影音',
                'statistics' => $statistics,
                'availableSeasons' => $availableSeasons,
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.drama')
                ->with('result', [
                    'status' => false,
                    'msg' => '載入觀看紀錄失敗：' . $e->getMessage()
                ]);
        }
    }

    /**
     * 取得集數觀看紀錄資料（AJAX）
     */
    public function viewLogsData(Request $request, $id)
    {
        try {
            $filters = $request->all();

            // 取得搜尋參數
            $searchParams = $request->input('search_params', []);
            if (!empty($searchParams)) {
                $filters = array_merge($filters, $searchParams);
            }

            $result = $this->viewService->getEpisodeViewLogs('drama', $id, $filters);

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
