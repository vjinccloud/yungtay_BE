<?php

namespace Modules\HistoryOrder\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\HistoryOrder\Backend\Model\HistoryOrder;

class HistoryOrderRepository extends BaseRepository
{
    public function __construct(HistoryOrder $model)
    {
        parent::__construct($model);
    }

    /**
     * 分頁查詢歷史訂單列表
     */
    public function getListPaginated($request, int $perPage = 10)
    {
        $query = $this->model->query();

        // 更新日期篩選
        if ($request->filled('date')) {
            $query->whereDate('updated_at', $request->input('date'));
        }

        // 訂單名稱搜尋
        if ($request->filled('order_name')) {
            $keyword = $request->input('order_name');
            $query->where('order_name', 'like', "%{$keyword}%");
        }

        // 系列型號篩選
        if ($request->filled('series_model')) {
            $keyword = $request->input('series_model');
            $query->where('series_model', 'like', "%{$keyword}%");
        }

        // 業務姓名搜尋
        if ($request->filled('sales_name')) {
            $keyword = $request->input('sales_name');
            $query->where('sales_name', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    /**
     * 取得所有歷史訂單（用於匯出）
     */
    public function getFilteredList($request)
    {
        $query = $this->model->query();

        // 若有指定 IDs，直接依 ID 篩選，忽略其他條件
        $ids = array_filter((array) $request->input('ids', []), 'is_numeric');
        if (!empty($ids)) {
            return $query->whereIn('id', $ids)->orderByDesc('updated_at')->get();
        }

        if ($request->filled('date')) {
            $query->whereDate('updated_at', $request->input('date'));
        }

        if ($request->filled('order_name')) {
            $keyword = $request->input('order_name');
            $query->where('order_name', 'like', "%{$keyword}%");
        }

        if ($request->filled('series_model')) {
            $keyword = $request->input('series_model');
            $query->where('series_model', 'like', "%{$keyword}%");
        }

        if ($request->filled('sales_name')) {
            $keyword = $request->input('sales_name');
            $query->where('sales_name', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('updated_at')->get();
    }
}
