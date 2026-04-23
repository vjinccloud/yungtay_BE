<?php

namespace Modules\RegisterBonus\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\RegisterBonus\Model\RegisterBonusSetting;

class RegisterBonusRepository extends BaseRepository
{
    public function __construct(RegisterBonusSetting $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得設定（自動建立）
     */
    public function getSetting(): RegisterBonusSetting
    {
        return $this->model->firstOrCreate(['id' => 1], [
            'is_active' => true,
            'bonus_amount' => 100,
            'expiry_type' => 'unlimited',
            'expiry_days' => null,
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
            'is_active' => $item->is_active,
            'bonus_amount' => $item->bonus_amount,
            'expiry_type' => $item->expiry_type,
            'expiry_days' => $item->expiry_days ?? 1,
        ];
    }

    /**
     * 儲存設定
     */
    public function saveSetting(array $attributes): RegisterBonusSetting
    {
        $record = $this->getSetting();

        if (($attributes['expiry_type'] ?? '') === 'unlimited') {
            $attributes['expiry_days'] = null;
        }

        $record->update($attributes);
        return $record;
    }
}
