<?php

namespace Modules\FrontMenuSetting\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\FrontMenuSetting\Model\FrontMenu;

/**
 * FrontMenuSetting 前台選單管理 - Repository
 */
class FrontMenuRepository extends BaseRepository
{
    public function __construct(FrontMenu $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得所有選單（排序後）
     */
    public function getAllOrdered()
    {
        return $this->model->ordered()->get();
    }

    /**
     * 取得啟用的選單（排序後）
     */
    public function getActiveOrdered()
    {
        return $this->model->active()->ordered()->get();
    }

    /**
     * 取得單筆
     */
    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * 新增選單
     */
    public function createMenu(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * 更新選單
     */
    public function updateMenu($id, array $data)
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        return $record;
    }

    /**
     * 刪除選單
     */
    public function deleteMenu($id)
    {
        $record = $this->findOrFail($id);
        return $record->delete();
    }

    /**
     * 取得某父層下的子選單
     */
    public function getChildrenByParentId($parentId)
    {
        return $this->model->where('parent_id', $parentId)->ordered()->get();
    }

    /**
     * 取得子孫 ID 列表（遞迴）
     */
    public function getDescendantIds($parentId): array
    {
        $ids = [];
        $children = $this->model->where('parent_id', $parentId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->getDescendantIds($childId));
        }

        return $ids;
    }

    /**
     * 取得所有子孫選單（含物件）
     */
    public function getDescendants($id): array
    {
        $descendants = [];
        $children = $this->model->where('parent_id', $id)->get();

        foreach ($children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $this->getDescendants($child->id));
        }

        return $descendants;
    }

    /**
     * 批次更新排序
     */
    public function batchUpdateSort(array $items, string $column = null): void
    {
        foreach ($items as $index => $item) {
            $this->model->where('id', $item['id'])->update(['seq' => $index]);
        }
    }

    /**
     * 批次更新樹狀排序（含 parent_id / level 變更）
     *
     * @param array $items  扁平化陣列 [{id, parent_id, seq}, ...]
     */
    public function batchUpdateTreeSort(array $items): void
    {
        // 先建立 parent_id → level 的映射
        $levelMap = [0 => 0]; // parent_id=0 代表根層，其 children level=0

        // 按照傳入順序更新，同時計算 level
        foreach ($items as $item) {
            $parentId = (int) ($item['parent_id'] ?? 0);
            $seq = (int) ($item['seq'] ?? 0);

            // 計算 level
            if ($parentId === 0) {
                $level = 0;
            } elseif (isset($levelMap[$parentId])) {
                $level = $levelMap[$parentId];
            } else {
                // 查詢 parent 的 level
                $parent = $this->model->find($parentId);
                $level = $parent ? $parent->level + 1 : 0;
            }

            $this->model->where('id', $item['id'])->update([
                'parent_id' => $parentId,
                'seq' => $seq,
                'level' => $level,
            ]);

            // 記錄此節點的 children 應有的 level
            $levelMap[$item['id']] = $level + 1;
        }
    }

    /**
     * 更新子選單層級（遞迴）
     */
    public function updateChildrenLevel($parentId)
    {
        $parent = $this->model->find($parentId);
        if (!$parent) {
            return;
        }

        $children = $this->model->where('parent_id', $parentId)->get();
        foreach ($children as $child) {
            $child->update(['level' => $parent->level + 1]);
            $this->updateChildrenLevel($child->id);
        }
    }
}
