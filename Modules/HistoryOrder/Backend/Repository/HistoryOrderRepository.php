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

        // 客戶姓名搜尋
        if ($request->filled('customer_name')) {
            $keyword = $request->input('customer_name');
            $query->where('customer_name', 'like', "%{$keyword}%");
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

        if ($request->filled('date')) {
            $query->whereDate('updated_at', $request->input('date'));
        }

        if ($request->filled('customer_name')) {
            $keyword = $request->input('customer_name');
            $query->where('customer_name', 'like', "%{$keyword}%");
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
