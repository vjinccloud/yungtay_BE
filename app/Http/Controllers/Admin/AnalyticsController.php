<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Traits\ValidatesAnalyticsParams;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

/**
 * 數據分析 Controller
 *
 * 處理後台數據報表的 Inertia 頁面請求
 *
 * 安全性：
 * - 使用 ValidatesAnalyticsParams Trait 驗證排序參數，防止 SQL 注入
 * - 所有排序欄位都經過白名單驗證
 * - 日期參數經過格式驗證
 */
class AnalyticsController extends Controller
{
    use ValidatesAnalyticsParams;

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * 新聞分類統計
     *
     * @param Request $request
     * @return Response
     */
    public function articles(Request $request): Response
    {
        // 接收日期區間參數
        $filters = $request->only(['start_date', 'end_date']);

        // 預設日期區間：最近一個月（結束日期為今天）
        $filters['end_date'] = $this->validateDate($filters['end_date'] ?? null) ?? Carbon::today()->format('Y-m-d');
        $filters['start_date'] = $this->validateDate($filters['start_date'] ?? null) ?? Carbon::today()->subMonth()->format('Y-m-d');

        // 處理搜尋參數（從 search_params 中取得）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');
            $filters = array_merge($filters, $searchParams);
        }

        // 驗證排序參數（防止 SQL 注入）
        $validated = $this->validateSortParams(
            $request->input('sortColumn'),
            $request->input('sortDirection')
        );

        // 驗證分頁參數
        $perPage = $this->validatePerPage($request->input('length'));

        $categories = $this->analyticsService->paginateArticleCategories(
            $perPage,
            $validated['sortColumn'],
            $validated['sortDirection'],
            $filters
        );

        return Inertia::render('Admin/Analytics/ArticleAnalytics', [
            'categories' => $categories,
            'dateRange' => [
                'start' => $filters['start_date'],
                'end' => $filters['end_date'],
            ],
        ]);
    }

    /**
     * 廣播分類統計
     *
     * @param Request $request
     * @return Response
     */
    public function radios(Request $request): Response
    {
        // 接收日期區間參數
        $filters = $request->only(['start_date', 'end_date']);

        // 預設日期區間：最近一個月（結束日期為今天）
        $filters['end_date'] = $this->validateDate($filters['end_date'] ?? null) ?? Carbon::today()->format('Y-m-d');
        $filters['start_date'] = $this->validateDate($filters['start_date'] ?? null) ?? Carbon::today()->subMonth()->format('Y-m-d');

        // 處理搜尋參數（從 search_params 中取得）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');
            $filters = array_merge($filters, $searchParams);
        }

        // 驗證排序參數（防止 SQL 注入）
        $validated = $this->validateSortParams(
            $request->input('sortColumn'),
            $request->input('sortDirection')
        );

        // 驗證分頁參數
        $perPage = $this->validatePerPage($request->input('length'));

        $categories = $this->analyticsService->paginateRadioCategories(
            $perPage,
            $validated['sortColumn'],
            $validated['sortDirection'],
            $filters
        );

        return Inertia::render('Admin/Analytics/RadioAnalytics', [
            'categories' => $categories,
            'dateRange' => [
                'start' => $filters['start_date'],
                'end' => $filters['end_date'],
            ],
        ]);
    }

    /**
     * 影音主分類統計
     *
     * @param Request $request
     * @return Response
     */
    public function dramaMainCategories(Request $request): Response
    {
        // 接收日期區間參數
        $filters = $request->only(['start_date', 'end_date']);

        // 預設日期區間：最近一個月（結束日期為今天）
        $filters['end_date'] = $this->validateDate($filters['end_date'] ?? null) ?? Carbon::today()->format('Y-m-d');
        $filters['start_date'] = $this->validateDate($filters['start_date'] ?? null) ?? Carbon::today()->subMonth()->format('Y-m-d');

        // 處理搜尋參數（從 search_params 中取得）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');
            $filters = array_merge($filters, $searchParams);
        }

        // 驗證排序參數（防止 SQL 注入）
        $validated = $this->validateSortParams(
            $request->input('sortColumn'),
            $request->input('sortDirection')
        );

        // 驗證分頁參數
        $perPage = $this->validatePerPage($request->input('length'));

        $categories = $this->analyticsService->paginateDramaMainCategories(
            $perPage,
            $validated['sortColumn'],
            $validated['sortDirection'],
            $filters
        );

        return Inertia::render('Admin/Analytics/DramaMainCategories', [
            'categories' => $categories,
            'dateRange' => [
                'start' => $filters['start_date'],
                'end' => $filters['end_date'],
            ],
        ]);
    }

    /**
     * 影音子分類統計
     *
     * @param Request $request
     * @return Response
     */
    public function dramaSubCategories(Request $request): Response
    {
        // 接收日期區間參數
        $filters = $request->only(['start_date', 'end_date']);

        // 預設日期區間：最近一個月（結束日期為今天）
        $filters['end_date'] = $this->validateDate($filters['end_date'] ?? null) ?? Carbon::today()->format('Y-m-d');
        $filters['start_date'] = $this->validateDate($filters['start_date'] ?? null) ?? Carbon::today()->subMonth()->format('Y-m-d');

        // 處理搜尋參數（從 search_params 中取得，包含 parent_category_id）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');

            // ✅ 確保 parent_category_id 是整數類型
            if (isset($searchParams['parent_category_id'])) {
                $searchParams['parent_category_id'] = (int) $searchParams['parent_category_id'];
            }

            $filters = array_merge($filters, $searchParams);
        }

        // 驗證排序參數（防止 SQL 注入）
        $validated = $this->validateSortParams(
            $request->input('sortColumn'),
            $request->input('sortDirection')
        );

        // 驗證分頁參數
        $perPage = $this->validatePerPage($request->input('length'));

        $categories = $this->analyticsService->paginateDramaSubCategories(
            $perPage,
            $validated['sortColumn'],
            $validated['sortDirection'],
            $filters
        );

        // 取得影音主分類清單（用於搜尋下拉選單）
        $mainCategories = $this->analyticsService->getDramaMainCategoriesForFilter();

        return Inertia::render('Admin/Analytics/SubCategoriesAnalytics', [
            'contentType' => 'drama',  // ✅ 傳入內容類型
            'categories' => $categories,
            'mainCategories' => $mainCategories,
            'dateRange' => [
                'start' => $filters['start_date'],
                'end' => $filters['end_date'],
            ],
            'filters' => [
                'parent_category_id' => $filters['parent_category_id'] ?? null,
            ],
        ]);
    }

    /**
     * 節目主分類統計
     *
     * @param Request $request
     * @return Response
     */
    public function programMainCategories(Request $request): Response
    {
        // 接收日期區間參數
        $filters = $request->only(['start_date', 'end_date']);

        // 預設日期區間：最近一個月（結束日期為今天）
        $filters['end_date'] = $this->validateDate($filters['end_date'] ?? null) ?? Carbon::today()->format('Y-m-d');
        $filters['start_date'] = $this->validateDate($filters['start_date'] ?? null) ?? Carbon::today()->subMonth()->format('Y-m-d');

        // 處理搜尋參數（從 search_params 中取得）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');
            $filters = array_merge($filters, $searchParams);
        }

        // 驗證排序參數（防止 SQL 注入）
        $validated = $this->validateSortParams(
            $request->input('sortColumn'),
            $request->input('sortDirection')
        );

        // 驗證分頁參數
        $perPage = $this->validatePerPage($request->input('length'));

        $categories = $this->analyticsService->paginateProgramMainCategories(
            $perPage,
            $validated['sortColumn'],
            $validated['sortDirection'],
            $filters
        );

        return Inertia::render('Admin/Analytics/ProgramMainCategories', [
            'categories' => $categories,
            'dateRange' => [
                'start' => $filters['start_date'],
                'end' => $filters['end_date'],
            ],
        ]);
    }

    /**
     * 節目子分類統計
     *
     * @param Request $request
     * @return Response
     */
    public function programSubCategories(Request $request): Response
    {
        // 接收日期區間參數
        $filters = $request->only(['start_date', 'end_date']);

        // 預設日期區間：最近一個月（結束日期為今天）
        $filters['end_date'] = $this->validateDate($filters['end_date'] ?? null) ?? Carbon::today()->format('Y-m-d');
        $filters['start_date'] = $this->validateDate($filters['start_date'] ?? null) ?? Carbon::today()->subMonth()->format('Y-m-d');

        // 處理搜尋參數（從 search_params 中取得，包含 parent_category_id）
        if ($request->has('search_params')) {
            $searchParams = $request->input('search_params');

            // ✅ 確保 parent_category_id 是整數類型
            if (isset($searchParams['parent_category_id'])) {
                $searchParams['parent_category_id'] = (int) $searchParams['parent_category_id'];
            }

            $filters = array_merge($filters, $searchParams);
        }

        // 驗證排序參數（防止 SQL 注入）
        $validated = $this->validateSortParams(
            $request->input('sortColumn'),
            $request->input('sortDirection')
        );

        // 驗證分頁參數
        $perPage = $this->validatePerPage($request->input('length'));

        $categories = $this->analyticsService->paginateProgramSubCategories(
            $perPage,
            $validated['sortColumn'],
            $validated['sortDirection'],
            $filters
        );

        // 取得節目主分類清單（用於搜尋下拉選單）
        $mainCategories = $this->analyticsService->getProgramMainCategoriesForFilter();

        return Inertia::render('Admin/Analytics/SubCategoriesAnalytics', [
            'contentType' => 'program',  // ✅ 傳入內容類型
            'categories' => $categories,
            'mainCategories' => $mainCategories,
            'dateRange' => [
                'start' => $filters['start_date'],
                'end' => $filters['end_date'],
            ],
            'filters' => [
                'parent_category_id' => $filters['parent_category_id'] ?? null,
            ],
        ]);
    }
}
