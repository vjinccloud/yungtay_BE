<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\RadioTheme;
use App\Services\CategoryService;
use App\Services\RadioService;
use App\Services\RadioThemeService;
use App\Http\Requests\RadioTheme\RadioThemeRequest;
class RadioThemeController extends Controller
{
       public function __construct(
           private CategoryService $categoryService,
           private RadioService $radioService,
           private RadioThemeService $radioThemeService
       ) {
       }

   /**
     * 顯示廣播主題列表
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_active']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order'; // 默認排序列為 sort_order
        $sortDirection = $request->input('sortDirection') ?? 'asc'; // 默認排序方向為升序
        $perPage = $request->input('length') ?? '10';

        $themes = $this->radioThemeService->paginate($perPage, $sortColumn, $sortDirection, $filters);

        return Inertia::render('Admin/RadioThemes/Index', compact('themes'));
    }

    /**
     * 顯示新增廣播主題表單
     */
    public function create()
    {
        $categories = $this->categoryService->getRadioCategories();
        $radios = $this->radioService->getAllRadioOptions();
        return Inertia::render('Admin/RadioThemes/Form', [
            'categories' => $categories,
            'radios' => $radios,
        ]);
    }

    /**
     * 儲存新的廣播主題
     */
    public function store(RadioThemeRequest $request)
    {
        $data = $request->validated();
        $result = $this->radioThemeService->save($data);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
     * 顯示編輯廣播主題表單
     */
    public function edit(string $id)
    {
        // 取得主題名稱
        $themeData = $this->radioThemeService->findName($id);

        if (!$themeData) {
            return redirect()->route('admin.radio-themes')
                ->withErrors(['error' => '主題不存在']);
        }

        $categories = $this->categoryService->getRadioCategories();
        $radios = $this->radioService->getAllRadioOptions();

        // 組合 theme 物件以符合前端期望的格式
        $theme = [
            'id' => $id,
            'name' => $themeData['name']
        ];

        return Inertia::render('Admin/RadioThemes/Form', [
            'categories' => $categories,
            'radios' => $radios,
            'theme' => $theme,
            // 保留舊的格式以維持向後相容
            'themeId' => $id,
            'themeName' => $themeData['name'],
        ]);
    }

    /**
     * 更新廣播主題
     */
    public function update(RadioThemeRequest $request, string $id)
    {
        $data = $request->validated();
        $result = $this->radioThemeService->save($data,$id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
     * 刪除廣播主題
     */
    public function destroy($id)
    {
        $result = $this->radioThemeService->delete($id);
         return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        // 驗證請求
        $validated = $request->validate([
            'id' => 'required|exists:radio_themes,id',
        ]);

        $result = $this->radioThemeService->checkedStatus($validated['id']);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
    * AJAX 取得廣播主題列表資料
    */
    public function ajaxList(Request $request)
    {
        $themeId = $request->input('theme_id');

        if (!$themeId) {
            return response()->json([
                'draw' => $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        $perPage = $request->input('length', 10);
        $page = ($request->input('start', 0) / $perPage) + 1;

        $result = $this->radioThemeService->getThemeRadios($themeId, $perPage, $page);

        return response()->json($result);
    }
    /**
     * 更新主題下廣播的排序
     */
    public function updateRelationSort(Request $request)
    {
        $validated = $request->validate([
            'radio_ids' => 'required|array',
            'radio_ids.*' => 'required|integer|exists:radio_theme_relations,id',
            'themeId' => 'required|integer|exists:radio_themes,id'
        ]);
        $result = $this->radioThemeService->updateRelationSortOrder($validated);

        return redirect()->back()->with('result', $result);
    }

   /**
     * 從主題中移除廣播
     *
     * @param int $id 關聯 ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeRadio($id)
    {
        $result = $this->radioThemeService->removeThemeRadio($id);
         return redirect()
        ->back()
        ->with('result', $result);
    }


    /**
     * 排序
     */
    public function sort(Request $request)
    {
        $ids = $request->input('ids', []);
        $result = $this->radioThemeService->sort($ids);

        return redirect()->back()->with('result', $result);
    }

}