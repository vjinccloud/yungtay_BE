<?php
// app/Repositories/ProgramThemeRelationRepository.php
namespace App\Repositories;

use App\Models\ProgramThemeRelation;
use Illuminate\Support\Facades\DB;
use App\Traits\ThemeRelationRepositoryTrait;

class ProgramThemeRelationRepository extends BaseRepository
{
    use ThemeRelationRepositoryTrait;
    public function __construct(ProgramThemeRelation $model)
    {
        parent::__construct($model);
    }
    
    /**
     * 實作 ThemeRelationRepositoryTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'program';
    }
    
    protected function getContentIdField(): string
    {
        return 'program_id';
    }
    
    protected function getContentRelationName(): string
    {
        return 'program';
    }

    /**
     * 取得主題下的節目列表（保留包裝方法以保持向後相容）
     */
    public function getThemePrograms(int $themeId, int $perPage = 10, int $page = 1)
    {
        return $this->getThemeContent($themeId, $perPage, $page);
    }

    // updateSortOrder 方法已由 ThemeRelationRepositoryTrait 提供

    // deleteRelation 方法已由 ThemeRelationRepositoryTrait 提供
    // reindexThemeSort 方法已由 ThemeRelationRepositoryTrait 提供
}
