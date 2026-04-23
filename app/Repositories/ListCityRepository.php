<?php

namespace App\Repositories;

use App\Models\ListCity;

class ListCityRepository extends BaseRepository
{
    public function __construct(ListCity $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得所有城市列表（格式化）
     */
    public function getAllCities()
    {
        return $this->model->orderBy('sn', 'asc')
            ->get()
            ->map(function ($city) {
                return [
                    'id' => $city->sn,
                    'name' => $city->getTranslation('title', app()->getLocale())
                ];
            });
    }
}