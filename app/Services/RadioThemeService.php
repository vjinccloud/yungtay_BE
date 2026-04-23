<?php

namespace App\Services;

use App\Repositories\RadioThemeRepository;
use App\Repositories\RadioThemeRelationRepository;
use App\Traits\ThemeServiceTrait;

class RadioThemeService extends BaseService
{
    use ThemeServiceTrait;

    public function __construct(
        private RadioThemeRepository $radioTheme,
        private RadioThemeRelationRepository $radioThemeRelation
    ) {
        parent::__construct($radioTheme);
        $this->initializeThemeSorting();  // 初始化主題排序設定
    }

    /**
     * 實作 ThemeServiceTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'radio';
    }

    protected function getThemeTypeChinese(): string
    {
        return '廣播主題';
    }

    protected function getThemeRepository()
    {
        return $this->radioTheme;
    }

    protected function getRelationRepository()
    {
        return $this->radioThemeRelation;
    }

    protected function getEditRouteName(): string
    {
        return 'admin.radio-themes.edit';
    }


    /**
     * 取得主題下的廣播列表（向後相容）
     */
    public function getThemeRadios($themeId, int $perPage = 10, int $page = 1)
    {
        return $this->getThemeContents($themeId, $perPage, $page);
    }




    /**
     * 從主題中移除廣播（向後相容）
     */
    public function removeThemeRadio(int $relationId): array
    {
        return $this->removeThemeContent($relationId);
    }

    /**
     * 取得前台廣播主題列表（向後相容）
     */
    public function getFrontendThemesWithRadios($radiosPerTheme = null)
    {
        return $this->getFrontendThemesWithContents($radiosPerTheme);
    }

}