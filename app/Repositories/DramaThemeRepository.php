<?php

namespace App\Repositories;

use App\Models\DramaTheme;
use App\Models\DramaThemeRelation;
use Illuminate\Support\Facades\DB;
use App\Traits\ThemeRepositoryTrait;

class DramaThemeRepository extends BaseRepository
{
    use ThemeRepositoryTrait;
    public function __construct(DramaTheme $model)
    {
        parent::__construct($model);
    }
    
    /**
     * 實作 ThemeRepositoryTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'drama';
    }
    
    protected function getRelationModelClass(): string
    {
        return DramaThemeRelation::class;
    }
    
    protected function getContentIdField(): string
    {
        return 'drama_id';
    }

    // save 方法已由 ThemeRepositoryTrait 提供
    // addContentRelation 方法已由 ThemeRepositoryTrait 提供

    /**
     * 根據 ID 查詢主題並只回傳格式化的名稱
     */
    public function findName($id)
    {
        $dramaTheme = $this->find($id);

        if (!$dramaTheme) {
            return null;
        }

        return [
            'name' => [
                'zh_TW' => $dramaTheme->getTranslation('name', 'zh_TW'),
                'en' => $dramaTheme->getTranslation('name', 'en'),
            ]
        ];
    }

    // paginate 方法已由 ThemeRepositoryTrait 提供，但需要覆寫以加入特定邏輯
    public function paginate($perPage, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        return $this->model->orderBy($sortColumn, $sortDirection)
            ->withCount('dramas')  // 預先載入影音數量
            ->with('updater:id,name')  // 預先載入更新者資訊
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($theme) => [
                'id' => $theme->id,
                'name_zh' => $theme->getTranslation('name', 'zh_TW'),
                'name_en' => $theme->getTranslation('name', 'en'),
                'is_active' => (bool) $theme->is_active,
                'sort_order' => $theme->sort_order,
                'created_at' => $theme->created_at->toDateTimeString(),
                'updated_at' => $theme->updated_at->toDateTimeString(),
                'dramas_count' =>  $theme->dramas_count,
                'updated_by_name' => $theme->updater ? $theme->updater->name : null,
            ]);
    }

    // updateSort 方法已由 ThemeRepositoryTrait 提供

    // delete 方法已由 ThemeRepositoryTrait 提供
    // reorderAll 方法已由 ThemeRepositoryTrait 提供

    /**
     * 取得前台顯示的主題列表（包含影音）
     * 使用 Trait 的共用方法
     */
    public function getActiveThemesWithDramas($dramasPerTheme = null)
    {
        return $this->getActiveThemesWithContents($dramasPerTheme);
    }

    // getActiveThemesForFilter 方法已由 ThemeRepositoryTrait 提供
}
