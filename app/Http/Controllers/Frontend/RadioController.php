<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Services\RadioService;
use App\Services\RadioThemeService;
use App\Models\Category;
use Illuminate\Http\Request;

class RadioController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private RadioService $radioService,
        private RadioThemeService $radioThemeService
    ) {}

    /**
     * 廣播列表頁面（共用 media/index.blade.php 模板）
     */
    public function index(Request $request)
    {
        // 取得模組 SEO
        $moduleSEO = $this->radioService->getModuleSEO('radio');
        $metaOverride = $moduleSEO ?? [];

        // 取得主題和相關廣播
        $themes = $this->radioThemeService->getFrontendThemesWithRadios();

        // 取得篩選用的主題列表
        $filterThemes = $this->radioThemeService->getFrontendThemesForFilter();

        // 取得廣播的主分類和子分類（用於篩選）
        $categories = $this->categoryService->getCategoriesForForm(Category::TYPE_RADIO);

        // 轉換主題格式以符合通用模板（radios -> items）
        $themes = collect($themes)->map(function ($theme) {
            $theme['items'] = $theme['radios'] ?? [];
            return $theme;
        })->toArray();

        return view('frontend.media.index', [
            'type' => 'radio',
            'typeName' => __('frontend.nav.radio'),
            'themes' => $themes,
            'filterThemes' => $filterThemes,
            'categories' => $categories,
            'metaOverride' => $metaOverride
        ]);
    }

    /**
     * 廣播詳情頁面
     */
    public function show(Request $request, $id)
    {
        $radio = $this->radioService->getFrontendRadioDetail($id);
           // 取得詳情頁 SEO
        $metaOverride = $this->radioService->getDetailSEO($radio);
        return view('frontend.radio.show', compact('radio', 'metaOverride'));
    }

    /**
     * 串流廣播音訊檔案
     */
    public function streamAudio(Request $request, string $filePath)
    {
        // 使用共用的 streamFile 方法，指定 audio/mpeg 類型
        return $this->streamFile($request, $filePath, 'audio/mpeg');
    }
}
