<?php

namespace App\Repositories;

use App\Models\ListArea;

class ListAreaRepository extends BaseRepository
{
    public function __construct(ListArea $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得所有地區列表（包含所屬縣市 ID）
     */
    public function getAllAreas()
    {
        return $this->model->orderBy('city_sn', 'asc')
            ->orderBy('sn', 'asc')
            ->get()
            ->map(function ($area) {
                return [
                    'id' => $area->sn,
                    'name' => $area->getTranslation('title', app()->getLocale()),
                    'city_id' => $area->city_sn
                ];
            });
    }

    /**
     * 根據城市取得格式化的區域列表
     */
    public function getAreasByCity($citySn)
    {
        return $this->model->where('city_sn', $citySn)
            ->orderBy('sn', 'asc')
            ->get()
            ->map(function ($area) {
                return [
                    'id' => $area->sn,
                    'name' => $area->getTranslation('title', app()->getLocale()),
                    'city_id' => $area->city_sn
                ];
            });
    }
}