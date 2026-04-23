<?php

namespace Modules\RegisterBonus\Backend\Service;

use Modules\RegisterBonus\Backend\Repository\RegisterBonusRepository;

class RegisterBonusService
{
    public function __construct(
        private RegisterBonusRepository $repository
    ) {}

    /**
     * 取得設定資料（編輯用）
     */
    public function getFormData(): array
    {
        return $this->repository->getDetail();
    }

    /**
     * 儲存設定
     */
    public function save(array $attributes): array
    {
        $this->repository->saveSetting($attributes);
        return [
            'status' => true,
            'msg' => '儲存成功',
        ];
    }
}
