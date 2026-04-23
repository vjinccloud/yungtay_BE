<?php

namespace Modules\SalesLocationImage\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\SalesLocationImage\Backend\Request\SalesLocationImageRequest;
use Modules\SalesLocationImage\Backend\Service\SalesLocationImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalesLocationImageController extends Controller
{
    protected SalesLocationImageService $service;

    public function __construct(SalesLocationImageService $service)
    {
        $this->service = $service;
    }

    /**
     * 編輯頁面
     */
    public function edit()
    {
        $data = $this->service->getFormData();

        return Inertia::render('Admin/SalesLocationImage/Form', [
            'data' => $data,
        ]);
    }

    /**
     * 更新儲存
     */
    public function update(SalesLocationImageRequest $request)
    {
        $result = $this->service->update($request->validated());
        $data = $this->service->getFormData();

        return Inertia::render('Admin/SalesLocationImage/Form', [
            'result' => $result,
            'data' => $data,
        ]);
    }
}
