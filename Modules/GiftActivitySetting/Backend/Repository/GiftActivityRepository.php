<?php

namespace Modules\GiftActivitySetting\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\GiftActivitySetting\Model\GiftActivity;

class GiftActivityRepository extends BaseRepository
{
    public function __construct(GiftActivity $model)
    {
        parent::__construct($model);
    }

    public function getListPaginated($request, int $perPage = 20)
    {
        $query = $this->model->newQuery()->latest();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where('title', 'like', "%{$keyword}%");
        }

        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }

        return $query->paginate($perPage);
    }

    public function getDetail(int $id): array
    {
        $item = $this->model->findOrFail($id);

        return [
            'id'                    => $item->id,
            'title'                 => $item->title,
            'start_date'            => $item->start_date?->format('Y-m-d'),
            'end_date'              => $item->end_date?->format('Y-m-d'),
            'status'                => $item->status,
            'condition_type'        => $item->condition_type,
            'condition_amount'      => $item->condition_amount ?? 0,
            'condition_category_ids' => $item->condition_category_ids ?? [],
            'gift_products'         => $item->gift_products ?? [],
        ];
    }

    public function store(array $attributes): GiftActivity
    {
        return $this->model->create($attributes);
    }

    public function updateById(int $id, array $attributes): GiftActivity
    {
        $record = $this->model->findOrFail($id);
        $record->update($attributes);
        return $record;
    }
}
