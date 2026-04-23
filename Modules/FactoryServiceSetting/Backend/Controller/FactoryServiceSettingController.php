<?php

namespace Modules\FactoryServiceSetting\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\FactoryServiceSetting\Backend\Service\FactoryServiceSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FactoryServiceSettingController extends Controller
{
    protected FactoryServiceSettingService $service;

    public function __construct(FactoryServiceSettingService $service)
    {
        $this->service = $service;
    }

    /**
     * 矩陣設定頁面
     */
    public function index()
    {
        $data = $this->service->getMatrixData();

        return Inertia::render('Admin/FactoryServiceSetting/Index', [
            'regions' => $data['regions'],
            'productServices' => $data['productServices'],
            'relations' => $data['relations'],
        ]);
    }

    /**
     * 儲存關聯設定
     */
    public function store(Request $request)
    {
        $result = $this->service->saveRelations($request->relations ?? []);

        return response()->json($result);
    }

    /**
     * 切換單一關聯
     */
    public function toggle(Request $request)
    {
        $result = $this->service->toggleRelation(
            $request->factory_id,
            $request->product_service_id
        );

        return response()->json($result);
    }
}
