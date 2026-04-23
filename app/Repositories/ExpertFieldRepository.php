<?php

namespace App\Repositories;

use App\Models\ExpertField;
use Illuminate\Support\Facades\DB;

class ExpertFieldRepository extends BaseRepository
{
    public function __construct(ExpertField $model)
    {
        parent::__construct($model);
    }

    /**
     * 分頁列表
     */
    public function paginate($perPage, $sortColumn = 'sort_order', $sortDirection = 'asc', $filters = [])
    {
        return $this->model->orderBy($sortColumn, $sortDirection)
            ->withCount('experts')
            ->filter($filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn($field) => [
                'id' => $field->id,
                'name_zh' => $field->getTranslation('name', 'zh_TW'),
                'is_active' => (bool) $field->is_active,
                'sort_order' => $field->sort_order,
                'experts_count' => $field->experts_count,
                'created_at' => $field->created_at->toDateTimeString(),
                'updated_at' => $field->updated_at->toDateTimeString(),
            ]);
    }

    /**
     * 取得所有啟用的領域（給下拉選單用）
     */
    public function getActiveFields()
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($field) => [
                'id' => $field->id,
                'name' => $field->getTranslation('name', 'zh_TW'),
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
