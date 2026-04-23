<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DramaThemeService;
use App\Services\DramaService;
use App\Services\CategoryService;
use App\Models\Category;

class DramaController extends Controller
{
    public function __construct(
        private DramaThemeService $dramaThemeService,
        private DramaService $dramaService,
        private CategoryService $categoryService
    ) {}

    /**
     * 顯示影音列表頁面
     */
    public function index()
    {
        // 取得模組 SEO
        $moduleSEO = $this->dramaService->getModuleSEO('drama');
        $metaOverride =  $moduleSEO ?? [];
       
        // 取得主題和相關影音
        $themes = $this->dramaThemeService->getFrontendThemesWithDramas();

        // 取得篩選用的主題列表
        $filterThemes = $this->dramaThemeService->getFrontendThemesForFilter();

        // 取得影音的主分類和子分類（用於篩選）
        $categories = $this->categoryService->getCategoriesForForm(Category::TYPE_DRAMA);

        // 轉換主題格式以符合通用模板
        $themes = collect($themes)->map(function ($theme) {
            $theme['items'] = $theme['dramas'] ?? [];
            return $theme;
        })->toArray();

        return view('frontend.media.index', [
            'type' => 'drama',
            'typeName' => __('messages.page_title.drama_type'),
            'themes' => $themes,
            'filterThemes' => $filterThemes,
            'categories' => $categories,
            'metaOverride' => $metaOverride
        ]);
    }

    /**
     * 影音詳情頁
     */
    public function show($id)
    {
        $drama = $this->dramaService->getFrontendDetail($id);
        
        if (!$drama) {
            abort(404);
        }
        
        // 取得詳情頁 SEO
        $metaOverride = $this->dramaService->getDetailSEO($drama);
        
        return view('frontend.drama.show', compact('drama', 'metaOverride'));
    }
}
