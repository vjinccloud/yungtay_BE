<?php

namespace Modules\PromotionActivity\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\PromotionActivity\Backend\Request\PromotionActivityRequest;
use Modules\PromotionActivity\Backend\Service\PromotionActivityService;
use Inertia\Inertia;

class PromotionActivityController extends Controller
{
    public function __construct(
        private PromotionActivityService $service
    ) {}

    /**
     * 顯示設定頁面
     */
    public function edit()
    {
        $data = $this->service->getFormData();
        $categories = $this->service->getCategoriesForSelect();

        return Inertia::render('Admin/PromotionActivity/Form', [
            'data' => $data,
            'categories' => $categories,
        ]);
    }

    /**
     * 更新設定
     */
    public function update(PromotionActivityRequest $request)
    {
        $result = $this->service->save($request->validated());
        $data = $this->service->getFormData();
        $categories = $this->service->getCategoriesForSelect();
        $result['redirect'] = route('admin.promotion-activity');

        return Inertia::render('Admin/PromotionActivity/Form', [
            'result' => $result,
            'data' => $data,
            'categories' => $categories,
        ]);
    }
}
