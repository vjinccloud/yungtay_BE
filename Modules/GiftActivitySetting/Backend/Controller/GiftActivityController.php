<?php

namespace Modules\GiftActivitySetting\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\GiftActivitySetting\Backend\Request\GiftActivityRequest;
use Modules\GiftActivitySetting\Backend\Service\GiftActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GiftActivityController extends Controller
{
    protected GiftActivityService $service;

    public function __construct(GiftActivityService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $items = $this->service->getListPaginated($request);

        return Inertia::render('Admin/GiftActivitySetting/Index', [
            'items'   => $items,
            'filters' => [
                'keyword' => $request->input('keyword', ''),
                'status'  => $request->input('status', ''),
            ],
        ]);
    }

    public function create()
    {
        $categories   = $this->service->getCategoriesForSelect();
        $giftProducts = $this->service->getGiftProductsForSelect();

        return Inertia::render('Admin/GiftActivitySetting/Form', [
            'data'         => null,
            'isEdit'       => false,
            'categories'   => $categories,
            'giftProducts' => $giftProducts,
        ]);
    }

    public function store(GiftActivityRequest $request)
    {
        $result = $this->service->store($request->validated());

        return redirect()->route('admin.gift-activity-settings.index')
                         ->with('result', $result);
    }

    public function edit($id)
    {
        $data         = $this->service->getFormData($id);
        $categories   = $this->service->getCategoriesForSelect();
        $giftProducts = $this->service->getGiftProductsForSelect();

        return Inertia::render('Admin/GiftActivitySetting/Form', [
            'data'         => $data,
            'isEdit'       => true,
            'categories'   => $categories,
            'giftProducts' => $giftProducts,
        ]);
    }

    public function update(GiftActivityRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return redirect()->route('admin.gift-activity-settings.index')
                         ->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->service->destroy($id);

        return redirect()->route('admin.gift-activity-settings.index')
                         ->with('result', $result);
    }
}
