<?php

namespace App\Repositories;

use App\Models\RadioTheme;
use App\Models\RadioThemeRelation;
use Illuminate\Support\Facades\DB;
use App\Traits\ThemeRepositoryTrait;

class RadioThemeRepository extends BaseRepository
{
    use ThemeRepositoryTrait;
    public function __construct(RadioTheme $model)
    {
        parent::__construct($model);
    }

    /**
     * 實作 ThemeRepositoryTrait 所需的抽象方法
     */
    protected function getContentType(): string
    {
        return 'radio';
    }

    protected function getRelationModelClass(): string
    {
        return RadioThemeRelation::class;
    }

    protected function getContentIdField(): string
    {
        return 'radio_id';
    }

    // save 方法已由 ThemeRepositoryTrait 提供
    // addContentRelation 方法已由 ThemeRepositoryTrait 提供

    /**
     * 根據 ID 查詢主題並只回傳格式化的名稱
     */
    public function findName($id)
    {
        $radioTheme = $this->find($id);

        if (!$radioTheme) {
            return null;
        }

        return [
            'name' => [
                'zh_TW' => $radioTheme->getTranslation('name', 'zh_TW'),
                'en' => $radioTheme->getTranslation('name', 'en'),
            ]
        ];
    }

    // paginate 方法已由 ThemeRepositoryTrait 提供，但需要覆寫以加入特定邏輯
    public function paginate($perPage, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        return $this->model->orderBy($sortColumn, $sortDirection)
            ->withCount('radios')  // 預先載入廣播數量
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
                'radios_count' =>  $theme->radios_count,
                'updated_by_name' => $theme->updater ? $theme->updater->name : null,
            ]);
    }

    // updateSort 方法已由 ThemeRepositoryTrait 提供

    // delete 方法已由 ThemeRepositoryTrait 提供
    // reorderAll 方法已由 ThemeRepositoryTrait 提供

    /**
     * 取得前台顯示的主題列表（包含廣播）
     * 使用 Trait 的共用方法
     */
    public function getActiveThemesWithRadios($radiosPerTheme = null)
    {
        return $this->getActiveThemesWithContents($radiosPerTheme);
    }

    // getActiveThemesForFilter 方法已由 ThemeRepositoryTrait 提供
}