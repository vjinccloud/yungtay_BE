<?php

namespace Modules\HomeImageSetting\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\HomeImageSetting\Backend\Request\HomeImageSettingRequest;
use Modules\HomeImageSetting\Backend\Service\HomeImageSettingService;
use Inertia\Inertia;

class HomeImageSettingController extends Controller
{
    protected HomeImageSettingService $service;

    public function __construct(HomeImageSettingService $service)
    {
        $this->service = $service;
    }

    /**
     * 顯示設定頁面
     */
    public function edit()
    {
        $data = $this->service->getFormData();

        return Inertia::render('Admin/HomeImageSetting/Form', [
            'data' => $data,
        ]);
    }

    /**
     * 更新設定
     */
    public function update(HomeImageSettingRequest $request)
    {
        $result = $this->service->save($request->validated());
        $data = $this->service->getFormData();
        $result['redirect'] = route('admin.home-image-setting');

        return Inertia::render('Admin/HomeImageSetting/Form', [
            'result' => $result,
            'data' => $data,
        ]);
    }
}
