<?php
// app/Repositories/ProgramThemeRepository.php

namespace App\Repositories;

use App\Models\ProgramTheme;
use App\Models\ProgramThemeRelation;
use App\Traits\ThemeRepositoryTrait;

class ProgramThemeRepository extends BaseRepository
{
    use ThemeRepositoryTrait;
    public function __construct(ProgramTheme $model)
    {
        parent::__construct($model);
    }
    
    /**
     * 實作 ThemeRepositoryTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'program';
    }
    
    protected function getRelationModelClass(): string
    {
        return ProgramThemeRelation::class;
    }
    
    protected function getContentIdField(): string
    {
        return 'program_id';
    }

    // save 方法已由 ThemeRepositoryTrait 提供
    // addContentRelation 方法已由 ThemeRepositoryTrait 提供

    /**
     * 根據 ID 取得主題名稱
     */
    public function findName($id)
    {
        $theme = $this->find($id);
        if (!$theme) return null;

        return [
            'name' => [
                'zh_TW' => $theme->getTranslation('name', 'zh_TW'),
                'en'    => $theme->getTranslation('name', 'en'),
            ],
        ];
    }

    /**
     * 取得分頁資料（補上 withCount('programs')，讓 programs_count 正確）
     */
    public function paginate($perPage = 10, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        $query = $this->model
            ->withCount('programs') // ← 與影音版對齊
            ->filter($filters);

        // 排序白名單
        $allowedSortColumns = ['id', 'sort_order', 'is_active', 'created_at', 'updated_at'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'sort_order';
        }

        return $query->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($theme) => [
                'id'            => $theme->id,
                'name'          => [
                    'zh_TW' => $theme->getTranslation('name', 'zh_TW'),
                    'en'    => $theme->getTranslation('name', 'en'),
                ],
                'sort_order'    => $theme->sort_order,
                'is_active'     => (bool) $theme->is_active,
                'programs_count'=> $theme->programs_count, // 來自 withCount
                'created_at'    => $theme->created_at->format('Y-m-d H:i:s'),
                'updated_at'    => $theme->updated_at->format('Y-m-d H:i:s'),
            ]);
    }

    // updateSort 方法已由 ThemeRepositoryTrait 提供

    // delete 方法已由 ThemeRepositoryTrait 提供
    // reorderAll 方法已由 ThemeRepositoryTrait 提供

    /**
     * 取得前台用主題 + 節目
     * 使用 Trait 的共用方法
     */
    public function getActiveThemesWithPrograms($programsPerTheme = null)
    {
        return $this->getActiveThemesWithContents($programsPerTheme);
    }

    // getActiveThemesForFilter 方法已由 ThemeRepositoryTrait 提供

}
