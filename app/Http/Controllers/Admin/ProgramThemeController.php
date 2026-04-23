<?php
// app/Http/Controllers/Admin/ProgramThemeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProgramThemeService;
use App\Services\ProgramService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramThemeController extends Controller
{
    public function __construct(
        private ProgramThemeService $programThemeService,
        private ProgramService $programService,
        private \App\Services\CategoryService $categoryService
    ) {
    }

    /**
     * 顯示節目主題列表
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order';
        $sortDirection = $request->input('sortDirection') ?? 'asc';
        $perPage = $request->input('length') ?? '10';

        $themes = $this->programThemeService->paginate(
            $perPage,
            $sortColumn,
            $sortDirection,
            $filters
        );

        return Inertia::render('Admin/ProgramThemes/Index', compact('themes'));
    }

    /**
     * 顯示新增節目主題表單
     */
    public function create()
    {
        $categories = $this->categoryService->getProgramCategories();
        $programs = $this->programService->getAllProgramOptions();
        return Inertia::render('Admin/ProgramThemes/Form', [
            'categories' => $categories,
            'programs' => $programs,
        ]);
    }

    /**
     * 儲存新節目主題
     */
    public function store(Request $request)
    {
        $result = $this->programThemeService->save($request->all());

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 顯示編輯節目主題表單
     */
    public function edit($id)
    {
        // 取得主題名稱
        $themeData = $this->programThemeService->findName($id);

        if (!$themeData) {
            return redirect()->route('admin.program-themes')
                ->withErrors(['error' => '主題不存在']);
        }

        $categories = $this->categoryService->getProgramCategories();
        $programs = $this->programService->getAllProgramOptions();
        return Inertia::render('Admin/ProgramThemes/Form', [
            'categories' => $categories,
            'programs' => $programs,
            'themeId' => $id,
            'themeName' => $themeData['name'], // 帶入名稱資料
        ]);
    }

    /**
     * 更新節目主題
     */
    public function update(Request $request, $id)
    {
        $result = $this->programThemeService->save($request->all(), $id);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 刪除節目主題（RESTful 資源路由）
     * 與 resourceWithPermissions 的 DELETE /program-themes/{id} 對應
     */
    public function destroy($id)
    {
        $result = $this->programThemeService->delete($id);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $result = $this->programThemeService->toggleActive($request->input('id'));

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 更新排序
     */
    public function sort(Request $request)
    {
        $result = $this->programThemeService->sort($request->input('items', []));

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 取得節目主題的節目列表
     */
    public function getPrograms($themeId)
    {
        $theme = $this->programThemeService->find($themeId);

        if (!$theme) {
            return response()->json([
                'status' => false,
                'msg' => '找不到該節目主題'
            ], 404);
        }

        $programs = $this->programThemeService->getThemePrograms($themeId);

        return response()->json([
            'status' => true,
            'programs' => $programs,
            'theme' => $theme
        ]);
    }

    /**
     * 新增節目到主題
     */
    public function addProgram(Request $request, $themeId)
    {
        $result = $this->programThemeService->addProgramToTheme(
            $themeId,
            $request->input('program_id')
        );

        return response()->json($result);
    }

    /**
     * 從主題中移除節目
     */
    public function removeProgram(Request $request, $themeId)
    {
        // 使用與影音主題相同的邏輯，透過 program_id 找到 relation_id
        $programId = $request->input('program_id');
        
        // 查找關聯 ID
        $relation = \App\Models\ProgramThemeRelation::where('theme_id', $themeId)
            ->where('program_id', $programId)
            ->first();
            
        if (!$relation) {
            return redirect()
                ->back()
                ->with('result', ['status' => false, 'msg' => '找不到該節目關聯']);
        }
        
        // 使用與影音相同的 removeThemeProgram 方法（傳入 relation_id）
        $result = $this->programThemeService->removeThemeProgram($relation->id);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 更新主題中節目的排序
     */
    public function sortPrograms(Request $request, $themeId)
    {
        // 使用與影音主題相同的方法名稱和參數格式
        $result = $this->programThemeService->updateRelationSortOrder([
            'themeId' => $themeId,
            'program_ids' => $request->input('programs', [])
        ]);

        // 使用 Inertia 標準回傳方式
        return redirect()->back()->with('result', $result);
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

        $result = $this->programThemeService->getThemProgram($themeId, $perPage, $page);

        return response()->json($result);
    }
}
