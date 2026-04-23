<?php

namespace Modules\ProductService\Backend\Service;

use App\Models\ProductService;
use Illuminate\Http\Request;

class ProductServiceService
{
    /**
     * 取得列表（支援分頁）
     */
    public function getList(Request $request = null)
    {
        $query = ProductService::ordered();

        // 關鍵字搜尋 (DataTable 的 search 參數)
        if ($request && $request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('name->zh_TW', 'like', "%{$keyword}%")
                  ->orWhere('name->en', 'like', "%{$keyword}%");
            });
        }

        // 排序
        if ($request) {
            $sortColumn = $request->input('sortColumn', 'sort');
            $sortDirection = $request->input('sortDirection', 'asc');
            
            $sortableColumns = ['id', 'sort', 'is_enabled'];
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
                'name_zh' => $item->getTranslation('name', 'zh_TW'),
                'name_en' => $item->getTranslation('name', 'en'),
                'sort' => $item->sort,
                'is_enabled' => $item->is_enabled,
            ];
        });
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getFormData($id)
    {
        $item = ProductService::findOrFail($id);

        return [
            'id' => $item->id,
            'name' => [
                'zh_TW' => $item->getTranslation('name', 'zh_TW'),
                'en' => $item->getTranslation('name', 'en'),
            ],
            'sort' => $item->sort ?? 0,
            'is_enabled' => $item->is_enabled ?? true,
        ];
    }

    /**
     * 新增
     */
    public function store(array $data)
    {
        ProductService::create($data);

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
        $item = ProductService::findOrFail($id);
        $item->update($data);

        return [
            'status' => true,
            'msg' => '更新成功',
        ];
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $item = ProductService::findOrFail($id);
        $item->delete();

        return [
            'status' => true,
            'msg' => '刪除成功',
        ];
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive($id)
    {
        $item = ProductService::findOrFail($id);
        $item->is_enabled = !$item->is_enabled;
        $item->save();

        return [
            'status' => true,
            'msg' => $item->is_enabled ? '已啟用' : '已停用',
        ];
    }

    /**
     * 更新排序
     */
    public function updateSort(array $items)
    {
        foreach ($items as $index => $item) {
            ProductService::where('id', $item['id'])->update(['sort' => $index]);
        }

        return [
            'status' => true,
            'msg' => '排序更新成功',
        ];
    }
}
