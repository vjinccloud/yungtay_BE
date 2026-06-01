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

        // 縣市地區搜尋
        if ($request->filled('case_area')) {
            $keyword = $request->input('case_area');
            $query->where('case_area', 'like', "%{$keyword}%");
        }

        // 系列型號篩選
        if ($request->filled('series_model')) {
            $keyword = $request->input('series_model');
            $query->where('series_model', 'like', "%{$keyword}%");
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

        if ($request->filled('case_area')) {
            $keyword = $request->input('case_area');
            $query->where('case_area', 'like', "%{$keyword}%");
        }

        if ($request->filled('series_model')) {
            $keyword = $request->input('series_model');
            $query->where('series_model', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('updated_at')->get();
    }
}
