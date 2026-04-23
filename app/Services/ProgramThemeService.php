<?php
// app/Service/ProgramThemeService.php

namespace App\Services;

use App\Repositories\ProgramThemeRepository;
use App\Repositories\ProgramThemeRelationRepository;
use App\Traits\ThemeServiceTrait;

class ProgramThemeService extends BaseService
{
    use ThemeServiceTrait;
    
    public function __construct(
        private ProgramThemeRepository $programTheme,
        private ProgramThemeRelationRepository $programThemeRelation
    ) {
        parent::__construct($programTheme);
        $this->initializeThemeSorting();  // 初始化主題排序設定
    }

    /**
     * 實作 ThemeServiceTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'program';
    }
    
    protected function getThemeTypeChinese(): string
    {
        return '節目主題';
    }
    
    protected function getThemeRepository()
    {
        return $this->programTheme;
    }
    
    protected function getRelationRepository()
    {
        return $this->programThemeRelation;
    }
    
    protected function getEditRouteName(): string
    {
        return 'admin.program-themes.edit';
    }


    /**
     * 取得主題下的節目列表（向後相容）
     */
    public function getThemePrograms($themeId, int $perPage = 10, int $page = 1)
    {
        return $this->getThemeContents($themeId, $perPage, $page);
    }


    /**
     * 從主題中移除節目（向後相容）
     */
    public function removeThemeProgram(int $relationId): array
    {
        return $this->removeThemeContent($relationId);
    }


    /**
     * 前台：主題 + 節目（向後相容）
     */
    public function getFrontendThemesWithPrograms($programsPerTheme = null)
    {
        return $this->getFrontendThemesWithContents($programsPerTheme);
    }

    /**
     * 向後相容：Controller 的 ajaxList 會呼叫此方法名稱
     */
    public function getThemProgram($id, int $perPage = 10, int $page = 1)
    {
        return $this->getThemeContents($id, $perPage, $page);
    }
}
