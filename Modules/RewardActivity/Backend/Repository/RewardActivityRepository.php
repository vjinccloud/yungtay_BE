<?php

namespace Modules\RewardActivity\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\RewardActivity\Model\RewardActivity;

class RewardActivityRepository extends BaseRepository
{
    public function __construct(RewardActivity $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得列表（分頁，支援搜尋）
     */
    public function getListPaginated($request, int $perPage = 20)
    {
        $query = $this->model->newQuery()->latest();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('promo_code', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }

        return $query->paginate($perPage);
    }

    /**
     * 取得詳細資料（編輯用）
     */
    public function getDetail(int $id): array
    {
        $item = $this->model->findOrFail($id);

        return [
            'id' => $item->id,
            'title' => $item->title,
            'start_date' => $item->start_date?->format('Y-m-d'),
            'end_date' => $item->end_date?->format('Y-m-d'),
            'description' => $item->description ?? '',
            'status' => $item->status,
            'show_on_frontend' => $item->show_on_frontend,
            'promo_code' => $item->promo_code ?? '',
            'condition_type' => $item->condition_type,
            'condition_order_total' => $item->condition_order_total ?? 0,
            'condition_category_ids' => $item->condition_category_ids ?? [],
            'reward_type' => $item->reward_type,
            'reward_value' => $item->reward_value,
            'credit_expiry_type' => $item->credit_expiry_type,
            'credit_expiry_days' => $item->credit_expiry_days ?? 1,
            'redemption_limit_type' => $item->redemption_limit_type,
            'redemption_site_total' => $item->redemption_site_total ?? 0,
        ];
    }

    /**
     * 建立
     */
    public function store(array $attributes): RewardActivity
    {
        return $this->model->create($attributes);
    }

    /**
     * 更新
     */
    public function updateById(int $id, array $attributes): RewardActivity
    {
        $record = $this->model->findOrFail($id);
        $record->update($attributes);
        return $record;
    }
}
