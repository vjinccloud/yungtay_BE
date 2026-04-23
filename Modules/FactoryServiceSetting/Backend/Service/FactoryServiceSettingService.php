<?php

namespace Modules\FactoryServiceSetting\Backend\Service;

use App\Models\Factory;
use App\Models\ProductService;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

class FactoryServiceSettingService
{
    /**
     * 取得矩陣資料
     */
    public function getMatrixData()
    {
        // 取得所有據點（含工廠）
        $regions = Region::with(['factories' => function ($query) {
            $query->ordered();
        }])->ordered()->get()->map(function ($region) {
            return [
                'id' => $region->id,
                'name' => $region->getTranslation('name', 'zh_TW'),
                'factories' => $region->factories->map(function ($factory) {
                    return [
                        'id' => $factory->id,
                        'name' => $factory->getTranslation('name', 'zh_TW'),
                    ];
                }),
            ];
        });

        // 取得所有產品服務
        $productServices = ProductService::enabled()->ordered()->get()->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->getTranslation('name', 'zh_TW'),
            ];
        });

        // 取得所有關聯
        $relations = DB::table('factory_product_service')
            ->get()
            ->map(function ($item) {
                return [
                    'factory_id' => $item->factory_id,
                    'product_service_id' => $item->product_service_id,
                ];
            });

        return [
            'regions' => $regions,
            'productServices' => $productServices,
            'relations' => $relations,
        ];
    }

    /**
     * 批量儲存關聯
     */
    public function saveRelations(array $relations)
    {
        try {
            DB::beginTransaction();

            // 清除所有現有關聯
            DB::table('factory_product_service')->truncate();

            // 建立新關聯
            $data = [];
            foreach ($relations as $relation) {
                $data[] = [
                    'factory_id' => $relation['factory_id'],
                    'product_service_id' => $relation['product_service_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($data)) {
                DB::table('factory_product_service')->insert($data);
            }

            DB::commit();

            return [
                'status' => true,
                'msg' => '儲存成功',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'msg' => '儲存失敗：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 切換單一關聯
     */
    public function toggleRelation($factoryId, $productServiceId)
    {
        $exists = DB::table('factory_product_service')
            ->where('factory_id', $factoryId)
            ->where('product_service_id', $productServiceId)
            ->exists();

        if ($exists) {
            DB::table('factory_product_service')
                ->where('factory_id', $factoryId)
                ->where('product_service_id', $productServiceId)
                ->delete();

            return [
                'status' => true,
                'checked' => false,
                'msg' => '已取消關聯',
            ];
        } else {
            DB::table('factory_product_service')->insert([
                'factory_id' => $factoryId,
                'product_service_id' => $productServiceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'status' => true,
                'checked' => true,
                'msg' => '已建立關聯',
            ];
        }
    }
}
