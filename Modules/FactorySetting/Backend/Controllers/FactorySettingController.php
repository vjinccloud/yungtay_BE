<?php

namespace Modules\FactorySetting\Backend\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\FactorySetting\Backend\Service\FactorySettingService;
use Modules\FactorySetting\Backend\Requests\FactorySettingRequest;

class FactorySettingController extends Controller
{
    protected FactorySettingService $service;

    public function __construct(FactorySettingService $service)
    {
        $this->service = $service;
    }

    /**
     * 列表頁面
     */
    public function index(Request $request)
    {
        $data = $this->service->getList($request);

        return Inertia::render('Admin/FactorySetting/Index', [
            'items' => $data['items'],
            'regions' => $data['regions'],
            'filters' => $request->only(['region_id', 'keyword']),
        ]);
    }

    /**
     * 編輯頁面
     */
    public function edit($id)
    {
        $item = $this->service->getById($id);

        if (!$item) {
            return redirect()->route('admin.factory-settings.index')
                ->with('error', '找不到該工廠');
        }

        return Inertia::render('Admin/FactorySetting/Form', [
            'item' => $item,
        ]);
    }

    /**
     * 更新資料
     */
    public function update(FactorySettingRequest $request, $id)
    {
        // Debug: 記錄收到的資料
        \Log::info('FactorySetting Update Request', [
            'id' => $id,
            'slim_image_zh' => $request->input('slim_image_zh') ? 'HAS DATA (' . strlen($request->input('slim_image_zh')) . ' chars)' : 'EMPTY',
            'slim_image_en' => $request->input('slim_image_en') ? 'HAS DATA (' . strlen($request->input('slim_image_en')) . ' chars)' : 'EMPTY',
            'slim_logo_zh' => $request->input('slim_logo_zh') ? 'HAS DATA (' . strlen($request->input('slim_logo_zh')) . ' chars)' : 'EMPTY',
            'slim_logo_en' => $request->input('slim_logo_en') ? 'HAS DATA (' . strlen($request->input('slim_logo_en')) . ' chars)' : 'EMPTY',
        ]);

        $result = $this->service->update($id, $request->validated());

        if ($result['status']) {
            return redirect()->route('admin.factory-settings.index')
                ->with('success', $result['msg']);
        }

        return back()->with('error', $result['msg']);
    }
}
