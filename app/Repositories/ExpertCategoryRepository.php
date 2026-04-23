<?php

namespace App\Repositories;

use App\Models\ExpertCategory;
use Illuminate\Support\Facades\DB;

class ExpertCategoryRepository extends BaseRepository
{
    public function __construct(ExpertCategory $model)
    {
        parent::__construct($model);
    }

    /**
     * 排序欄位映射（前端欄位名 => 資料庫欄位名）
     */
    protected function getSortColumnMap(): array
    {
        return [
            'name_zh' => 'name',
        ];
    }

    /**
     * 分頁列表
     */
    public function paginate($perPage, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        // 轉換排序欄位名稱
        $columnMap = $this->getSortColumnMap();
        $sortColumn = $columnMap[$sortColumn] ?? $sortColumn;

        return $this->model->orderBy($sortColumn, $sortDirection)
            ->withCount('experts')
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($category) => [
                'id' => $category->id,
                'name_zh' => $category->getTranslation('name', 'zh_TW'),
                'is_active' => (bool) $category->is_active,
                'sort_order' => $category->sort_order,
                'experts_count' => $category->experts_count,
                'created_at' => $category->created_at->toDateTimeString(),
                'updated_at' => $category->updated_at->toDateTimeString(),
            ]);
    }

    /**
     * 取得所有啟用的分類（給下拉選單用）
     */
    public function getActiveCategories()
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name_zh' => $category->getTranslation('name', 'zh_TW'),
            ]);
    }

    /**
     * 更新排序
     */
    public function updateSort(array $sortedIds)
    {
        DB::transaction(function () use ($sortedIds) {
            foreach ($sortedIds as $index => $id) {
                $this->model->where('id', $id)->update(['sort_order' => $index + 1]);
            }
        });
    }
}
