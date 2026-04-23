<?php
// app/Repositories/RadioThemeRelationRepository.php

namespace App\Repositories;

use App\Models\RadioThemeRelation;
use App\Traits\ThemeRelationRepositoryTrait;

class RadioThemeRelationRepository extends BaseRepository
{
    use ThemeRelationRepositoryTrait;
    public function __construct(RadioThemeRelation $model)
    {
        parent::__construct($model);
    }

    /**
     * 實作 ThemeRelationRepositoryTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'radio';
    }

    protected function getContentIdField(): string
    {
        return 'radio_id';
    }

    protected function getContentRelationName(): string
    {
        return 'radio';
    }

    /**
     * 取得主題下的廣播列表（保留包裝方法以保持向後相容）
     */
    public function getThemeRadios(int $themeId, int $perPage = 10, int $page = 1)
    {
        return $this->getThemeContent($themeId, $perPage, $page);
    }

    // updateSortOrder 方法已由 ThemeRelationRepositoryTrait 提供


    // deleteRelation 方法已由 ThemeRelationRepositoryTrait 提供
    // reindexThemeSort 方法已由 ThemeRelationRepositoryTrait 提供


}