<?php

namespace Modules\RegisterBonus\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\RegisterBonus\Backend\Request\RegisterBonusRequest;
use Modules\RegisterBonus\Backend\Service\RegisterBonusService;
use Inertia\Inertia;

class RegisterBonusController extends Controller
{
    public function __construct(
        private RegisterBonusService $service
    ) {}

    /**
     * 顯示設定頁面
     */
    public function edit()
    {
        $data = $this->service->getFormData();

        return Inertia::render('Admin/RegisterBonus/Form', [
            'data' => $data,
        ]);
    }

    /**
     * 更新設定
     */
    public function update(RegisterBonusRequest $request)
    {
        $result = $this->service->save($request->validated());
        $data = $this->service->getFormData();
        $result['redirect'] = route('admin.register-bonus');

        return Inertia::render('Admin/RegisterBonus/Form', [
            'result' => $result,
            'data' => $data,
        ]);
    }
}
