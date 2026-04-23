<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\DramaTheme;
use App\Services\CategoryService;
use App\Services\DramaService;
use App\Services\DramaThemeService;
use App\Http\Requests\DramaTheme\DramaThemeRequest;
class DramaThemeController extends Controller
{
       public function __construct(
           private CategoryService $categoryService,
           private DramaService $dramaService,
           private DramaThemeService $dramaThemeService
       ) {
       }

   /**
     * 顯示影音主題列表
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_active']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order'; // 默認排序列為 sort_order
        $sortDirection = $request->input('sortDirection') ?? 'asc'; // 默認排序方向為升序
        $perPage = $request->input('length') ?? '10';

        $themes = $this->dramaThemeService->paginate($perPage, $sortColumn, $sortDirection, $filters);

        return Inertia::render('Admin/DramaThemes/Index', compact('themes'));
    }

    /**
     * 顯示新增影音主題表單
     */
    public function create()
    {
        $categories = $this->categoryService->getDramaCategories();
        $dramas = $this->dramaService->getAllDramaOptions();
        return Inertia::render('Admin/DramaThemes/Form', [
            'categories' => $categories,
            'dramas' => $dramas,
        ]);
    }

    /**
     * 儲存新的影音主題
     */
    public function store(DramaThemeRequest $request)
    {
        $data = $request->validated();
        $result = $this->dramaThemeService->save($data);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
     * 顯示編輯影音主題表單
     */
    public function edit(string $id)
    {
        // 取得主題名稱
        $themeData = $this->dramaThemeService->findName($id);

        if (!$themeData) {
            return redirect()->route('admin.drama-themes')
                ->withErrors(['error' => '主題不存在']);
        }

        $categories = $this->categoryService->getDramaCategories();
        $dramas = $this->dramaService->getAllDramaOptions();
        
        // 組合 theme 物件以符合前端期望的格式
        $theme = [
            'id' => $id,
            'name' => $themeData['name']
        ];
        
        return Inertia::render('Admin/DramaThemes/Form', [
            'categories' => $categories,
            'dramas' => $dramas,
            'theme' => $theme,
            // 保留舊的格式以維持向後相容
            'themeId' => $id,
            'themeName' => $themeData['name'],
        ]);
    }

    /**
     * 更新影音主題
     */
    public function update(DramaThemeRequest $request, string $id)
    {
        $data = $request->validated();
        $result = $this->dramaThemeService->save($data,$id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
     * 刪除影音主題
     */
    public function destroy($id)
    {
        $result = $this->dramaThemeService->delete($id);
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
            'id' => 'required|exists:drama_themes,id',
        ]);

        $result = $this->dramaThemeService->checkedStatus($validated['id']);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
    * AJAX 取得影音主題列表資料
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

        $result = $this->dramaThemeService->getThemeDramas($themeId, $perPage, $page);

        return response()->json($result);
    }
    /**
     * 更新主題下影音的排序
     */
    public function updateRelationSort(Request $request)
    {
        $validated = $request->validate([
            'drama_ids' => 'required|array',
            'drama_ids.*' => 'required|integer|exists:drama_theme_relations,id',
            'themeId' => 'required|integer|exists:drama_themes,id'
        ]);
        $result = $this->dramaThemeService->updateRelationSortOrder($validated);

        return redirect()->back()->with('result', $result);
    }

   /**
     * 從主題中移除影音
     *
     * @param int $id 關聯 ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeDrama($id)
    {
        $result = $this->dramaThemeService->removeThemeDrama($id);
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
        $result = $this->dramaThemeService->sort($ids);

        return redirect()->back()->with('result', $result);
    }

}
