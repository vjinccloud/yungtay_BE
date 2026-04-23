<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * 主題關聯 Repository 共用邏輯
 * 用於 DramaThemeRelationRepository 和 ProgramThemeRelationRepository
 */
trait ThemeRelationRepositoryTrait
{
    /**
     * 取得內容類型 (drama 或 program)
     * @return string
     */
    abstract protected function getContentType(): string;
    
    /**
     * 取得內容欄位名稱 (drama_id 或 program_id)
     * @return string
     */
    abstract protected function getContentIdField(): string;
    
    /**
     * 取得內容關聯名稱 (drama 或 program)
     * @return string
     */
    abstract protected function getContentRelationName(): string;

    /**
     * 取得主題下的內容列表（支援 DataTable）
     *
     * @param int $themeId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getThemeContent(int $themeId, int $perPage = 10, int $page = 1)
    {
        $contentRelation = $this->getContentRelationName();
        $contentIdField = $this->getContentIdField();
        
        return $this->model
            ->newQuery()
            ->where('theme_id', $themeId)
            ->with([$contentRelation . ':id,title'])
            ->orderBy('sort_order', 'asc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($relation, int $index) use ($page, $perPage, $contentRelation, $contentIdField) {
                $content = $relation->$contentRelation;
                $contentName = $content 
                    ? $content->getTranslation('title', 'zh_TW')
                    : '(' . $this->getContentTypeName() . '已刪除)';
                
                return [
                    'DT_RowId' => 'row_' . $relation->id,
                    'id' => $relation->id,
                    $contentIdField => $relation->$contentIdField,
                    $this->getContentType() . '_name' => $contentName,
                    'sort_order' => $relation->sort_order,
                    'created_at' => $relation->created_at->format('Y-m-d H:i:s'),
                    // 計算正確的全域序號
                    'index' => ($page - 1) * $perPage + $index + 1,
                ];
            });
    }

    /**
     * 更新排序
     *
     * @param int $themeId
     * @param array $relationIds 關聯ID陣列
     * @return bool
     */
    public function updateSortOrder(int $themeId, array $relationIds): bool
    {
        return DB::transaction(function () use ($themeId, $relationIds) {
            foreach ($relationIds as $index => $id) {
                $this->model
                    ->where('id', $id)
                    ->where('theme_id', $themeId)
                    ->update(['sort_order' => $index + 1]);
            }
            return true;
        });
    }

    /**
     * 刪除主題內容關聯
     *
     * @param int $relationId
     * @return bool
     * @throws \Exception
     */
    public function deleteRelation(int $relationId): bool
    {
        return DB::transaction(function () use ($relationId) {
            $relation = $this->model->find($relationId);

            if (!$relation) {
                throw new \Exception('找不到該' . $this->getContentTypeName() . '關聯');
            }

            $themeId = $relation->theme_id;
            
            // 刪除關聯
            $deleted = $relation->delete();

            if ($deleted) {
                $this->reindexThemeSort($themeId);
            }

            return $deleted;
        });
    }

    /**
     * 重新整理主題的排序
     *
     * @param int $themeId
     * @return void
     */
    protected function reindexThemeSort(int $themeId): void
    {
        $relations = $this->model
            ->where('theme_id', $themeId)
            ->orderBy('sort_order', 'asc')
            ->get(['id']);

        foreach ($relations as $index => $relation) {
            $this->model
                ->where('id', $relation->id)
                ->update(['sort_order' => $index + 1]);
        }
    }
    
    /**
     * 取得內容類型中文名稱
     */
    protected function getContentTypeName(): string
    {
        return $this->getContentType() === 'drama' ? '影音' : '節目';
    }
}