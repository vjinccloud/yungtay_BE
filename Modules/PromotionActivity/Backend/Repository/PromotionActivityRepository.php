<?php

namespace Modules\PromotionActivity\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\PromotionActivity\Model\PromotionActivity;

class PromotionActivityRepository extends BaseRepository
{
    public function __construct(PromotionActivity $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得設定（自動建立）
     */
    public function getSetting(): PromotionActivity
    {
        return $this->model->firstOrCreate(['id' => 1], [
            'title' => '',
            'is_active' => true,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'min_amount' => 0,
            'discount_amount' => 0,
            'category_ids' => [],
        ]);
    }

    /**
     * 取得詳情（編輯用）
     */
    public function getDetail(): array
    {
        $item = $this->getSetting();

        return [
            'id' => $item->id,
            'title' => $item->title ?? '',
            'is_active' => $item->is_active ?? true,
            'start_date' => $item->start_date?->format('Y-m-d') ?? '',
            'end_date' => $item->end_date?->format('Y-m-d') ?? '',
            'min_amount' => $item->min_amount ?? 0,
            'discount_amount' => $item->discount_amount ?? 0,
            'category_ids' => $item->category_ids ?? [],
        ];
    }

    /**
     * 儲存設定
     */
    public function saveSetting(array $attributes): PromotionActivity
    {
        $record = $this->getSetting();
        $record->update($attributes);
        return $record;
    }
}
