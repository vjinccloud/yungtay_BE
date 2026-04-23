<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProgramThemeService;
use App\Services\ProgramService;
use App\Services\CategoryService;
use App\Models\Category;

class ProgramController extends Controller
{
    public function __construct(
        private ProgramThemeService $programThemeService,
        private ProgramService $programService,
        private CategoryService $categoryService
    ) {}

    /**
     * 顯示節目列表頁面
     */
    public function index()
    {
        // 取得模組 SEO
        $moduleSEO = $this->programService->getModuleSEO('program');
        $metaOverride = $moduleSEO ?? [];
        
        // 取得主題和相關節目
        $themes = $this->programThemeService->getFrontendThemesWithPrograms();

        // 取得篩選用的主題列表
        $filterThemes = $this->programThemeService->getFrontendThemesForFilter();

        // 取得節目的主分類和子分類（用於篩選）
        $categories = $this->categoryService->getCategoriesForForm(Category::TYPE_PROGRAM);

        // 轉換主題格式以符合通用模板
        $themes = collect($themes)->map(function ($theme) {
            $theme['items'] = $theme['programs'] ?? [];
            return $theme;
        })->toArray();

        return view('frontend.media.index', [
            'type' => 'program',
            'typeName' => __('messages.page_title.program_type'),
            'themes' => $themes,
            'filterThemes' => $filterThemes,
            'categories' => $categories,
            'metaOverride' => $metaOverride
        ]);
    }

    /**
     * 顯示節目影片列表
     */
    public function videos($programId)
    {
        // TODO: 實作節目影片列表頁面
        // 暫時重定向到節目列表
        return redirect()->route('program.index');
    }
}