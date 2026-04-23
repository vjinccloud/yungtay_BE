<?php

namespace Modules\HomeVideoSetting\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\HomeVideoSetting\Backend\Request\HomeVideoSettingRequest;
use Modules\HomeVideoSetting\Backend\Service\HomeVideoSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeVideoSettingController extends Controller
{
    protected HomeVideoSettingService $service;

    public function __construct(HomeVideoSettingService $service)
    {
        $this->service = $service;
    }

    /**
     * 列表頁
     */
    public function index()
    {
        $list = $this->service->getList();

        return Inertia::render('Admin/HomeVideoSetting/Index', [
            'list' => $list,
            'listCount' => count($list),
            'maxLimit' => 6,
        ]);
    }

    /**
     * 新增頁面
     */
    public function create()
    {
        // 檢查是否已達上限 6 筆
        $count = $this->service->getList()->count();
        if ($count >= 6) {
            return redirect()->route('admin.home-video-settings.index')
                ->with('error', '首頁影片最多只能新增 6 筆');
        }

        return Inertia::render('Admin/HomeVideoSetting/Form', [
            'data' => null,
            'isEdit' => false,
        ]);
    }

    /**
     * 新增儲存
     */
    public function store(HomeVideoSettingRequest $request)
    {
        // 檢查是否已達上限 6 筆
        $count = $this->service->getList()->count();
        if ($count >= 6) {
            return Inertia::render('Admin/HomeVideoSetting/Form', [
                'result' => [
                    'status' => false,
                    'msg' => '首頁影片最多只能新增 6 筆',
                    'redirect' => route('admin.home-video-settings.index'),
                ],
            ]);
        }

        $result = $this->service->store($request->validated());
        $result['redirect'] = route('admin.home-video-settings.index');

        return Inertia::render('Admin/HomeVideoSetting/Form', [
            'result' => $result,
        ]);
    }

    /**
     * 編輯頁面
     */
    public function edit($id)
    {
        $data = $this->service->getFormData($id);

        return Inertia::render('Admin/HomeVideoSetting/Form', [
            'data' => $data,
            'isEdit' => true,
        ]);
    }

    /**
     * 更新儲存
     */
    public function update(HomeVideoSettingRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());
        $data = $this->service->getFormData($id);
        $result['redirect'] = route('admin.home-video-settings.index');

        return Inertia::render('Admin/HomeVideoSetting/Form', [
            'result' => $result,
            'data' => $data,
            'isEdit' => true,
        ]);
    }

    /**
     * 刪除
     */
    public function destroy($id)
    {
        $result = $this->service->destroy($id);
        $list = $this->service->getList();

        return Inertia::render('Admin/HomeVideoSetting/Index', [
            'result' => $result,
            'list' => $list,
        ]);
    }

    /**
     * 更新排序
     */
    public function updateSort(Request $request)
    {
        $result = $this->service->updateSort($request->input('items', []));

        return response()->json($result);
    }
}
