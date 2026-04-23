<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Services\RadioService;
use App\Services\ViewService;
use App\Http\Requests\Radio\RadioRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RadioController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private RadioService $radioService
    ) {
    }

    /**
     * 顯示廣播列表
     */
    public function index(Request $request)
    {
        // 接收搜尋參數
        $filters = $request->only([
            'search',
            'category_id',
            'subcategory_id',
            'year',
            'is_active'
        ]);

        // 處理搜尋參數（從 search_params 中取得）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');
            $filters = array_merge($filters, $searchParams);
        }

        $sortColumn = $request->input('sortColumn') ?? 'publish_date';
        $sortDirection = $request->input('sortDirection') ?? 'desc';
        $perPage = $request->input('length') ?? '10';

        $radios = $this->radioService->paginate($perPage, $sortColumn, $sortDirection, $filters);

        // 取得分類資料給搜尋表單使用
        $categories = $this->categoryService->getRadioCategories();

        return Inertia::render('Admin/Radios/Index', [
            'radios' => $radios,
            'categories' => $categories['main'] ?? collect([])
        ]);
    }

    /**
     * 顯示新增表單
     */
    public function create()
    {
        $categories = $this->categoryService->getRadioCategories();

        // 取得暫存集數的最大季數（限制最低季數選擇）
        $maxSeasonWithEpisodes = $this->radioService->getMaxSeasonWithEpisodes(null);

        return Inertia::render('Admin/Radios/Form', [
            'categories' => $categories['main'] ?? collect([]),
            'subcategories' => $categories['sub'] ?? collect([]),
            'maxSeasonWithEpisodes' => $maxSeasonWithEpisodes
        ]);
    }

    /**
     * 儲存新廣播
     */
    public function store(RadioRequest $request)
    {
        $validated = $request->validated();
        $result = $this->radioService->save($validated);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 顯示編輯表單
     */
    public function edit($id)
    {
        $radio = $this->radioService->getFormData($id);
        $categories = $this->categoryService->getRadioCategories();

        // 取得該廣播集數的最大季數（限制最低季數選擇）
        $maxSeasonWithEpisodes = $this->radioService->getMaxSeasonWithEpisodes($id);

        return Inertia::render('Admin/Radios/Form', [
            'radio' => $radio,
            'categories' => $categories['main'] ?? collect([]),
            'subcategories' => $categories['sub'] ?? collect([]),
            'maxSeasonWithEpisodes' => $maxSeasonWithEpisodes
        ]);
    }

    /**
     * 更新廣播
     */
    public function update(RadioRequest $request, $id)
    {
        $validated = $request->validated();
        $result = $this->radioService->save($validated, $id);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 刪除廣播
     */
    public function destroy($id)
    {
        $result = $this->radioService->delete($id);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:radios,id',
        ]);

        $result = $this->radioService->checkedStatus($validated['id']);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 顯示觀看統計頁面
     */
    public function viewStats($id)
    {
        try {
            // 取得廣播資料
            $radio = $this->radioService->find($id);

            if (!$radio) {
                return redirect()
                    ->route('admin.radios.index')
                    ->with('result', [
                        'status' => false,
                        'msg' => '找不到廣播資料'
                    ]);
            }

            // 取得觀看統計數據
            $viewService = app(ViewService::class);
            $statistics = $viewService->getContentViewStatistics('radio', $id);

            return Inertia::render('Admin/Radios/ViewStats', [
                'contentId' => (int) $id,
                'contentTitle' => $radio->getTranslation('title', 'zh_TW') ?: '未命名廣播',
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.radios.index')
                ->with('result', [
                    'status' => false,
                    'msg' => '載入觀看統計失敗：' . $e->getMessage()
                ]);
        }
    }
}
