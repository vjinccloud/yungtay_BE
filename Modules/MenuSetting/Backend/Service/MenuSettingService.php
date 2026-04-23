<?php

namespace Modules\MenuSetting\Backend\Service;

use App\Models\AdminMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuSettingService
{
    /**
     * 清除側邊欄選單快取（所有管理員）
     */
    protected function clearMenuCache()
    {
        // 清除所有管理員的選單快取
        $adminUsers = \App\Models\AdminUser::pluck('id');
        foreach ($adminUsers as $userId) {
            Cache::forget('admin_menu0' . $userId);
            Cache::forget('admin_menu1' . $userId);
        }
    }
    /**
     * 取得列表（支援分頁）
     */
    public function getList(Request $request = null)
    {
        $query = AdminMenu::ordered();

        // 關鍵字搜尋
        if ($request && $request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('url', 'like', "%{$keyword}%")
                  ->orWhere('url_name', 'like', "%{$keyword}%");
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

            $sortableColumns = ['id', 'seq', 'title', 'level', 'status', 'type'];
            if (in_array($sortColumn, $sortableColumns)) {
                $query->reorder()->orderBy($sortColumn, $sortDirection);
            }
        }

        // 分頁
        $perPage = $request ? $request->input('length', 10) : 10;
        $paginated = $query->paginate($perPage);

        return $paginated->through(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'parent_id' => $item->parent_id,
                'parent_title' => $item->parent_id > 0 ? optional($item->parent)->title : '頂層',
                'type' => $item->type,
                'type_label' => $item->type == 1 ? '顯示' : '不顯示',
                'level' => $item->level,
                'url' => $item->url,
                'url_name' => $item->url_name,
                'icon_image' => $item->icon_image,
                'status' => $item->status,
                'seq' => $item->seq,
            ];
        });
    }

    /**
     * 取得樹狀選單列表（供 Modal 使用）
     */
    public function getTreeList()
    {
        $allMenus = AdminMenu::ordered()->get();

        $formatItem = function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'parent_id' => $item->parent_id,
                'type' => $item->type,
                'level' => $item->level,
                'url' => $item->url,
                'url_name' => $item->url_name,
                'icon_image' => $item->icon_image,
                'status' => $item->status,
                'seq' => $item->seq,
                'children' => [],
            ];
        };

        // 建立樹狀結構
        $map = [];
        $tree = [];

        foreach ($allMenus as $menu) {
            $map[$menu->id] = $formatItem($menu);
        }

        foreach ($map as $id => &$item) {
            if ($item['parent_id'] == 0) {
                $tree[] = &$item;
            } else {
                if (isset($map[$item['parent_id']])) {
                    $map[$item['parent_id']]['children'][] = &$item;
                } else {
                    // 父層不存在，歸入頂層
                    $tree[] = &$item;
                }
            }
        }
        unset($item);

        return $tree;
    }

    /**
     * 取得父層選單選項（排除自己及自己的子層）
     */
    public function getParentOptions($excludeId = null)
    {
        $query = AdminMenu::ordered();

        if ($excludeId) {
            // 排除自己及所有子孫
            $excludeIds = $this->getDescendantIds($excludeId);
            $excludeIds[] = (int) $excludeId;
            $query->whereNotIn('id', $excludeIds);
        }

        $menus = $query->get();

        $options = [
            ['value' => 0, 'label' => '頂層（無父層）'],
        ];

        foreach ($menus as $menu) {
            $prefix = str_repeat('　', $menu->level); // 用全形空白縮排表示層級
            $options[] = [
                'value' => $menu->id,
                'label' => $prefix . $menu->title,
            ];
        }

        return $options;
    }

    /**
     * 取得某選單所有子孫 ID
     */
    protected function getDescendantIds($parentId)
    {
        $ids = [];
        $children = AdminMenu::where('parent_id', $parentId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->getDescendantIds($childId));
        }

        return $ids;
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getFormData($id)
    {
        $item = AdminMenu::findOrFail($id);

        return [
            'id' => $item->id,
            'title' => $item->title,
            'parent_id' => $item->parent_id,
            'type' => $item->type,
            'level' => $item->level,
            'url' => $item->url,
            'url_name' => $item->url_name,
            'icon_image' => $item->icon_image,
            'status' => $item->status,
            'seq' => $item->seq,
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

        $parent = AdminMenu::find($parentId);
        return $parent ? $parent->level + 1 : 0;
    }

    /**
     * 新增
     */
    public function store(array $data)
    {
        $data['level'] = $this->calculateLevel($data['parent_id'] ?? 0);

        AdminMenu::create($data);
        $this->clearMenuCache();

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
        $item = AdminMenu::findOrFail($id);

        // 重新計算層級
        $data['level'] = $this->calculateLevel($data['parent_id'] ?? 0);

        $oldParentId = $item->parent_id;
        $item->update($data);

        // 如果父層改變，需要更新所有子孫的層級
        if ($oldParentId != ($data['parent_id'] ?? 0)) {
            $this->updateChildrenLevel($id);
        }

        $this->clearMenuCache();

        return [
            'status' => true,
            'msg' => '更新成功',
        ];
    }

    /**
     * 遞迴更新子選單的層級
     */
    protected function updateChildrenLevel($parentId)
    {
        $parent = AdminMenu::find($parentId);
        if (!$parent) {
            return;
        }

        $children = AdminMenu::where('parent_id', $parentId)->get();
        foreach ($children as $child) {
            $child->update(['level' => $parent->level + 1]);
            $this->updateChildrenLevel($child->id);
        }
    }

    /**
     * 取得某選單的所有子孫選單（含自己）
     */
    public function getDescendants($id)
    {
        $descendants = [];
        $children = AdminMenu::where('parent_id', $id)->get();

        foreach ($children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $this->getDescendants($child->id));
        }

        return $descendants;
    }

    /**
     * 取得刪除資訊（含子孫數量，供前端警告用）
     */
    public function getDeleteInfo($id)
    {
        $item = AdminMenu::findOrFail($id);
        $descendants = $this->getDescendants($id);
        $descendantCount = count($descendants);
        $descendantTitles = array_map(fn($d) => $d->title, $descendants);

        return [
            'id' => $item->id,
            'title' => $item->title,
            'has_children' => $descendantCount > 0,
            'descendant_count' => $descendantCount,
            'descendant_titles' => $descendantTitles,
        ];
    }

    /**
     * 刪除（含所有子孫選單及對應權限）
     */
    public function destroy($id)
    {
        $item = AdminMenu::findOrFail($id);

        // 先遞迴刪除所有子孫選單及其權限
        $this->destroyDescendants($id);

        // 刪除自身的 Spatie 權限
        if ($item->url_name) {
            $this->removePermission($item->url_name);
        }

        $item->delete();
        $this->clearMenuCache();

        return [
            'status' => true,
            'msg' => '刪除成功（已同步移除對應權限）',
        ];
    }

    /**
     * 遞迴刪除所有子孫選單及其權限
     */
    protected function destroyDescendants($parentId)
    {
        $children = AdminMenu::where('parent_id', $parentId)->get();

        foreach ($children as $child) {
            // 先刪子孫的子孫
            $this->destroyDescendants($child->id);

            // 移除權限
            if ($child->url_name) {
                $this->removePermission($child->url_name);
            }

            $child->delete();
        }
    }

    /**
     * 移除 Spatie 權限（含所有角色的關聯）
     */
    protected function removePermission($permissionName)
    {
        try {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'admin')
                ->first();

            if ($permission) {
                // 先從所有角色中移除這個權限
                $roles = Role::where('guard_name', 'admin')->get();
                foreach ($roles as $role) {
                    $role->revokePermissionTo($permission);
                }
                // 刪除權限本身
                $permission->delete();
                // 清除 Spatie 快取
                app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            }
        } catch (\Exception $e) {
            \Log::warning('移除權限失敗: ' . $permissionName . ' - ' . $e->getMessage());
        }
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive($id)
    {
        $item = AdminMenu::findOrFail($id);
        $item->status = !$item->status;
        $item->save();

        // 當關閉時，遞迴關閉所有子孫選單
        if (!$item->status) {
            $this->cascadeDisableChildren($id);
        }

        $this->clearMenuCache();

        return [
            'status' => true,
            'msg' => $item->status ? '已啟用' : '已停用（含所有子選單）',
        ];
    }

    /**
     * 遞迴關閉所有子孫選單
     */
    protected function cascadeDisableChildren($parentId)
    {
        $children = AdminMenu::where('parent_id', $parentId)->get();
        foreach ($children as $child) {
            $child->status = false;
            $child->save();
            $this->cascadeDisableChildren($child->id);
        }
    }

    /**
     * 更新排序
     */
    public function updateSort(array $items)
    {
        foreach ($items as $index => $item) {
            AdminMenu::where('id', $item['id'])->update(['seq' => $index]);
        }

        $this->clearMenuCache();

        return [
            'status' => true,
            'msg' => '排序更新成功',
        ];
    }
}
