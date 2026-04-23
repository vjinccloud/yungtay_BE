<?php

namespace Modules\FrontMenuSetting\Backend\Service;

use Modules\FrontMenuSetting\Backend\Repository\FrontMenuRepository;
use Illuminate\Http\Request;

/**
 * FrontMenuSetting 前台選單管理 - Service
 */
class FrontMenuService
{
    public function __construct(
        private FrontMenuRepository $repository
    ) {}

    protected function primaryLocale(): string
    {
        return config('translatable.primary', 'zh_TW');
    }

    protected function getTranslatableFields($model, string $attribute): array
    {
        $locales = array_keys(config('translatable.locales', ['zh_TW' => []]));
        $result = [];
        foreach ($locales as $locale) {
            $result[$locale] = $model->getTranslation($attribute, $locale) ?? '';
        }
        return $result;
    }

    /**
     * 取得列表（分頁，支援搜尋/排序）
     */
    public function getList(Request $request = null)
    {
        $query = $this->repository->getModel()->newQuery()->ordered();

        // 關鍵字搜尋
        if ($request && $request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('link_url', 'like', "%{$keyword}%");
            });
        }

        // 父層篩選
        if ($request && $request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // 排序
        if ($request) {
            $sortColumn = $request->input('sortColumn', 'seq');
            $sortDirection = $request->input('sortDirection', 'asc');

            $sortableColumns = ['id', 'seq', 'title', 'level', 'status'];
            if (in_array($sortColumn, $sortableColumns)) {
                $query->reorder()->orderBy($sortColumn, $sortDirection);
            }
        }

        // 分頁
        $perPage = $request ? $request->input('length', 20) : 20;
        $paginated = $query->paginate($perPage);

        return $paginated->through(function ($item) {
            return $this->formatListItem($item);
        });
    }

    /**
     * 取得樹狀選單列表
     */
    public function getTreeList()
    {
        $allMenus = $this->repository->getAllOrdered();

        $map = [];
        $tree = [];

        foreach ($allMenus as $menu) {
            $map[$menu->id] = [
                'id' => $menu->id,
                'title' => $menu->title,
                'title_primary' => $menu->getTranslation('title', $this->primaryLocale()),
                'parent_id' => $menu->parent_id,
                'level' => $menu->level,
                'link_type' => $menu->link_type,
                'link_url' => $menu->link_url,
                'link_target' => $menu->link_target,
                'icon' => $menu->icon,
                'seq' => $menu->seq,
                'status' => $menu->status,
                'children' => [],
            ];
        }

        foreach ($map as $id => &$item) {
            if ($item['parent_id'] == 0) {
                $tree[] = &$item;
            } else {
                if (isset($map[$item['parent_id']])) {
                    $map[$item['parent_id']]['children'][] = &$item;
                } else {
                    $tree[] = &$item;
                }
            }
        }
        unset($item);

        return $tree;
    }

    /**
     * 取得父層選單選項（排除自己及子孫）
     */
    public function getParentOptions($excludeId = null)
    {
        // 用樹狀結構保證父子順序正確
        $excludeIds = [];
        if ($excludeId) {
            $excludeIds = $this->repository->getDescendantIds($excludeId);
            $excludeIds[] = (int) $excludeId;
        }

        $tree = $this->getTreeList();

        $options = [
            ['value' => 0, 'label' => '頂層（無父層）', 'labelText' => '頂層（無父層）', 'level' => 0],
        ];

        $this->flattenTreeToOptions($tree, $options, $excludeIds);

        return $options;
    }

    /**
     * 遞迴將樹狀結構扁平化為選項列表（保證父子順序正確）
     */
    private function flattenTreeToOptions(array $nodes, array &$options, array $excludeIds = [])
    {
        foreach ($nodes as $node) {
            if (in_array($node['id'], $excludeIds)) {
                continue;
            }

            $title = $node['title_primary'] ?? $node['title'] ?? '';
            $options[] = [
                'value' => $node['id'],
                'label' => $title,
                'labelText' => $title,
                'level' => $node['level'] ?? 0,
            ];

            if (!empty($node['children'])) {
                $this->flattenTreeToOptions($node['children'], $options, $excludeIds);
            }
        }
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getFormData($id)
    {
        $item = $this->repository->findOrFail($id);

        return [
            'id' => $item->id,
            'parent_id' => $item->parent_id,
            'title' => $this->getTranslatableFields($item, 'title'),
            'level' => $item->level,
            'link_type' => $item->link_type,
            'link_url' => $item->link_url,
            'link_target' => $item->link_target,
            'icon' => $item->icon,
            'seq' => $item->seq,
            'status' => $item->status,
        ];
    }

    /**
     * 計算層級
     */
    protected function calculateLevel($parentId)
    {
        if ($parentId == 0) {
            return 0;
        }

        $parent = $this->repository->getModel()->find($parentId);
        return $parent ? $parent->level + 1 : 0;
    }

    /**
     * 新增
     */
    public function store(array $data)
    {
        $data['level'] = $this->calculateLevel($data['parent_id'] ?? 0);

        $this->repository->createMenu($data);

        return [
            'status' => true,
            'msg' => '新增成功',
        ];
    }

    /**
     * 更新
     */
    public function update($id, array $data)
    {
        $item = $this->repository->findOrFail($id);

        $data['level'] = $this->calculateLevel($data['parent_id'] ?? 0);

        $oldParentId = $item->parent_id;
        $this->repository->updateMenu($id, $data);

        // 如果父層改變，需要遞迴更新所有子孫的層級
        if ($oldParentId != ($data['parent_id'] ?? 0)) {
            $this->repository->updateChildrenLevel($id);
        }

        return [
            'status' => true,
            'msg' => '更新成功',
        ];
    }

    /**
     * 取得刪除資訊（含子孫數量，供前端警告用）
     */
    public function getDeleteInfo($id)
    {
        $item = $this->repository->findOrFail($id);
        $descendants = $this->repository->getDescendants($id);
        $descendantCount = count($descendants);
        $descendantTitles = array_map(fn($d) => $d->getTranslation('title', $this->primaryLocale()), $descendants);

        return [
            'id' => $item->id,
            'title' => $item->getTranslation('title', $this->primaryLocale()),
            'has_children' => $descendantCount > 0,
            'descendant_count' => $descendantCount,
            'descendant_titles' => $descendantTitles,
        ];
    }

    /**
     * 刪除（含所有子孫選單）
     */
    public function destroy($id)
    {
        // 先刪除所有子孫
        $this->destroyDescendants($id);

        // 刪除自身
        $this->repository->deleteMenu($id);

        return [
            'status' => true,
            'msg' => '刪除成功',
        ];
    }

    /**
     * 遞迴刪除所有子孫選單
     */
    protected function destroyDescendants($parentId)
    {
        $children = $this->repository->getChildrenByParentId($parentId);

        foreach ($children as $child) {
            $this->destroyDescendants($child->id);
            $child->delete();
        }
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive($id)
    {
        $item = $this->repository->findOrFail($id);
        $item->status = !$item->status;
        $item->save();

        // 停用時，遞迴停用所有子孫
        if (!$item->status) {
            $this->cascadeDisableChildren($id);
        }

        return [
            'status' => true,
            'msg' => $item->status ? '已啟用' : '已停用（含所有子選單）',
        ];
    }

    /**
     * 遞迴停用所有子選單
     */
    protected function cascadeDisableChildren($parentId)
    {
        $children = $this->repository->getChildrenByParentId($parentId);
        foreach ($children as $child) {
            $child->status = false;
            $child->save();
            $this->cascadeDisableChildren($child->id);
        }
    }

    /**
     * 更新排序（支援巢狀拖曳，含 parent_id / level 變更）
     *
     * 前端傳入扁平化陣列，每筆包含 id, parent_id, seq
     * 例: [{id:1, parent_id:0, seq:0}, {id:3, parent_id:1, seq:0}, ...]
     */
    public function updateSort(array $items)
    {
        $this->repository->batchUpdateTreeSort($items);

        return [
            'status' => true,
            'msg' => '排序更新成功',
        ];
    }

    /**
     * 取得前台用的樹狀選單（僅啟用的）
     */
    public function getFrontendTree($locale = 'zh_TW')
    {
        $allMenus = $this->repository->getActiveOrdered();

        $map = [];
        $tree = [];

        foreach ($allMenus as $menu) {
            $map[$menu->id] = [
                'id' => $menu->id,
                'title' => $menu->getTranslation('title', $locale),
                'link_type' => $menu->link_type,
                'link_url' => $menu->link_url,
                'link_target' => $menu->link_target,
                'icon' => $menu->icon,
                'children' => [],
            ];
        }

        foreach ($map as $id => &$item) {
            $menuModel = $allMenus->firstWhere('id', $id);
            if ($menuModel->parent_id == 0) {
                $tree[] = &$item;
            } else {
                if (isset($map[$menuModel->parent_id])) {
                    $map[$menuModel->parent_id]['children'][] = &$item;
                } else {
                    $tree[] = &$item;
                }
            }
        }
        unset($item);

        return $tree;
    }

    /**
     * 格式化列表項目
     */
    protected function formatListItem($item)
    {
        $primary = $this->primaryLocale();

        return [
            'id' => $item->id,
            'title' => $item->getTranslation('title', $primary),
            'parent_id' => $item->parent_id,
            'parent_title' => $item->parent_id > 0
                ? optional($item->parent)->getTranslation('title', $primary)
                : '頂層',
            'level' => $item->level,
            'link_type' => $item->link_type,
            'link_type_label' => $this->getLinkTypeLabel($item->link_type),
            'link_url' => $item->link_url,
            'link_target' => $item->link_target,
            'icon' => $item->icon,
            'seq' => $item->seq,
            'status' => $item->status,
        ];
    }

    /**
     * 取得連結類型標籤
     */
    protected function getLinkTypeLabel($type)
    {
        return match ($type) {
            'url' => '外部連結',
            'route' => '內部路由',
            'page' => '頁面',
            'none' => '無連結',
            default => $type,
        };
    }
}
