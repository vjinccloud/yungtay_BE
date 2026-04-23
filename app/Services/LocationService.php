<?php

namespace App\Services;

use App\Repositories\ListCityRepository;
use App\Repositories\ListAreaRepository;
use Illuminate\Support\Facades\Log;

class LocationService
{
    protected $cityRepository;
    protected $areaRepository;

    public function __construct(ListCityRepository $cityRepository, ListAreaRepository $areaRepository)
    {
        $this->cityRepository = $cityRepository;
        $this->areaRepository = $areaRepository;
    }

    /**
     * 取得城市列表
     */
    public function getCities()
    {
        try {
            return $this->cityRepository->getAllCities();
        } catch (\Exception $e) {
            Log::error('取得城市列表失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * 取得所有地區列表（包含所屬縣市 ID）
     */
    public function getAllAreas()
    {
        try {
            return $this->areaRepository->getAllAreas();
        } catch (\Exception $e) {
            Log::error('取得所有地區列表失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * 根據城市取得格式化的區域列表
     */
    public function getAreasByCity($citySn)
    {
        try {
            return $this->areaRepository->getAreasByCity($citySn);
        } catch (\Exception $e) {
            Log::error('取得區域列表失敗', [
                'city_sn' => $citySn,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }
}