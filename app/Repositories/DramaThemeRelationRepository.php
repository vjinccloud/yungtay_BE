<?php
// app/Repositories/DramaThemeRelationRepository.php

namespace App\Repositories;

use App\Models\DramaThemeRelation;
use App\Traits\ThemeRelationRepositoryTrait;

class DramaThemeRelationRepository extends BaseRepository
{
    use ThemeRelationRepositoryTrait;
    public function __construct(DramaThemeRelation $model)
    {
        parent::__construct($model);
    }
    
    /**
     * 實作 ThemeRelationRepositoryTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'drama';
    }
    
    protected function getContentIdField(): string
    {
        return 'drama_id';
    }
    
    protected function getContentRelationName(): string
    {
        return 'drama';
    }

    /**
     * 取得主題下的影音列表（保留包裝方法以保持向後相容）
     */
    public function getThemeDramas(int $themeId, int $perPage = 10, int $page = 1)
    {
        return $this->getThemeContent($themeId, $perPage, $page);
    }

    // updateSortOrder 方法已由 ThemeRelationRepositoryTrait 提供


    // deleteRelation 方法已由 ThemeRelationRepositoryTrait 提供
    // reindexThemeSort 方法已由 ThemeRelationRepositoryTrait 提供


}
