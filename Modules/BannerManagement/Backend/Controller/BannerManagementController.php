<?php

namespace Modules\BannerManagement\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\BannerManagement\Backend\Request\BannerManagementRequest;
use Modules\BannerManagement\Backend\Service\BannerManagementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BannerManagementController extends Controller
{
    protected BannerManagementService $service;

    public function __construct(BannerManagementService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $items = $this->service->getListPaginated($request);

        return Inertia::render('Admin/BannerManagement/Index', [
            'items'   => $items,
            'filters' => [
                'keyword' => $request->input('keyword', ''),
                'status'  => $request->input('status', ''),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/BannerManagement/Form', [
            'data'   => null,
            'isEdit' => false,
        ]);
    }

    public function store(BannerManagementRequest $request)
    {
        $result = $this->service->store($request->validated());

        return redirect()->route('admin.banner-management.index')
                         ->with('result', $result);
    }

    public function edit($id)
    {
        $data = $this->service->getFormData($id);

        return Inertia::render('Admin/BannerManagement/Form', [
            'data'   => $data,
            'isEdit' => true,
        ]);
    }

    public function update(BannerManagementRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return redirect()->route('admin.banner-management.index')
                         ->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->service->destroy($id);

        return redirect()->route('admin.banner-management.index')
                         ->with('result', $result);
    }
}
