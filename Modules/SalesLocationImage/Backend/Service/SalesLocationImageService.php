<?php

namespace Modules\SalesLocationImage\Backend\Service;

use Modules\SalesLocationImage\Backend\Repository\SalesLocationImageRepository;

class SalesLocationImageService
{
    protected SalesLocationImageRepository $repository;

    public function __construct(SalesLocationImageRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 取得表單資料
     */
    public function getFormData()
    {
        return $this->repository->getDetail();
    }

    /**
     * 儲存資料
     */
    public function update(array $data)
    {
        return $this->repository->saveSetting($data);
    }
}
