<?php

namespace App\Services;

use App\Repositories\DramaThemeRepository;
use App\Repositories\DramaThemeRelationRepository;
use App\Traits\ThemeServiceTrait;

class DramaThemeService extends BaseService
{
    use ThemeServiceTrait;
    
    public function __construct(
        private DramaThemeRepository $dramaTheme,
        private DramaThemeRelationRepository $dramaThemeRelation
    ) {
        parent::__construct($dramaTheme);
        $this->initializeThemeSorting();  // 初始化主題排序設定
    }

    /**
     * 實作 ThemeServiceTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'drama';
    }
    
    protected function getThemeTypeChinese(): string
    {
        return '影音主題';
    }
    
    protected function getThemeRepository()
    {
        return $this->dramaTheme;
    }
    
    protected function getRelationRepository()
    {
        return $this->dramaThemeRelation;
    }
    
    protected function getEditRouteName(): string
    {
        return 'admin.drama-themes.edit';
    }


    /**
     * 取得主題下的影音列表（向後相容）
     */
    public function getThemeDramas($themeId, int $perPage = 10, int $page = 1)
    {
        return $this->getThemeContents($themeId, $perPage, $page);
    }




    /**
     * 從主題中移除影音（向後相容）
     */
    public function removeThemeDrama(int $relationId): array
    {
        return $this->removeThemeContent($relationId);
    }

    /**
     * 取得前台影音主題列表（向後相容）
     */
    public function getFrontendThemesWithDramas($dramasPerTheme = null)
    {
        return $this->getFrontendThemesWithContents($dramasPerTheme);
    }

}
