<?php

namespace Modules\HistoryOrder\Backend\Service;

use Modules\HistoryOrder\Backend\Repository\HistoryOrderRepository;
use Modules\HistoryOrder\Backend\Model\HistoryOrder;

class HistoryOrderService
{
    public function __construct(
        private HistoryOrderRepository $repository
    ) {}

    /**
     * 取得列表（分頁）
     */
    public function getListPaginated($request)
    {
        return $this->repository->getListPaginated($request);
    }

    /**
     * 取得單筆詳情
     */
    public function getDetail(int $id)
    {
        return $this->repository->find($id);
    }

    /**
     * 取得系列型號選項
     */
    public function getSeriesModelOptions(): array
    {
        return HistoryOrder::getSeriesModelOptions();
    }

    /**
     * 取得篩選後的資料（用於匯出）
     */
    public function getFilteredList($request)
    {
        return $this->repository->getFilteredList($request);
    }
}
