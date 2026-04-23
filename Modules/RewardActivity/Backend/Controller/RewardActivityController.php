<?php

namespace Modules\RewardActivity\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\RewardActivity\Backend\Request\RewardActivityRequest;
use Modules\RewardActivity\Backend\Service\RewardActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RewardActivityController extends Controller
{
    protected RewardActivityService $service;

    public function __construct(RewardActivityService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $items = $this->service->getListPaginated($request);

        return Inertia::render('Admin/RewardActivity/Index', [
            'items'   => $items,
            'filters' => [
                'keyword' => $request->input('keyword', ''),
                'status'  => $request->input('status', ''),
            ],
        ]);
    }

    public function create()
    {
        $categories = $this->service->getCategoriesForSelect();

        return Inertia::render('Admin/RewardActivity/Form', [
            'data'       => null,
            'isEdit'     => false,
            'categories' => $categories,
        ]);
    }

    public function store(RewardActivityRequest $request)
    {
        $result = $this->service->store($request->validated());

        return redirect()->route('admin.reward-activities.index')
                         ->with('result', $result);
    }

    public function edit($id)
    {
        $data       = $this->service->getFormData($id);
        $categories = $this->service->getCategoriesForSelect();
        $readonly   = $data['status'] === 'active';

        return Inertia::render('Admin/RewardActivity/Form', [
            'data'       => $data,
            'isEdit'     => true,
            'readonly'   => $readonly,
            'categories' => $categories,
        ]);
    }

    public function update(RewardActivityRequest $request, $id)
    {
        $existing = $this->service->getFormData($id);

        if ($existing['status'] === 'active') {
            return redirect()->route('admin.reward-activities.index')
                             ->with('result', ['status' => false, 'msg' => '已啟用的活動無法編輯，僅能刪除']);
        }

        $result = $this->service->update($id, $request->validated());

        return redirect()->route('admin.reward-activities.index')
                         ->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->service->destroy($id);

        return redirect()->route('admin.reward-activities.index')
                         ->with('result', $result);
    }
}
