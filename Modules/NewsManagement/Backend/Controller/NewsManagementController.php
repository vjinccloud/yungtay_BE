<?php

namespace Modules\NewsManagement\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\NewsManagement\Backend\Request\NewsManagementRequest;
use Modules\NewsManagement\Backend\Service\NewsManagementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NewsManagementController extends Controller
{
    protected NewsManagementService $service;

    public function __construct(NewsManagementService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $items = $this->service->getListPaginated($request);
        $categories = $this->service->getCategories();

        return Inertia::render('Admin/NewsManagement/Index', [
            'items'      => $items,
            'categories' => $categories,
            'filters'    => [
                'keyword'     => $request->input('keyword', ''),
                'status'      => $request->input('status', ''),
                'category_id' => $request->input('category_id', ''),
            ],
        ]);
    }

    public function create()
    {
        $categories = $this->service->getCategories();

        return Inertia::render('Admin/NewsManagement/Form', [
            'data'       => null,
            'isEdit'     => false,
            'categories' => $categories,
        ]);
    }

    public function store(NewsManagementRequest $request)
    {
        $result = $this->service->store($request->validated());

        return redirect()->route('admin.news-management.index')
                         ->with('result', $result);
    }

    public function edit($id)
    {
        $data = $this->service->getFormData($id);
        $categories = $this->service->getCategories();

        return Inertia::render('Admin/NewsManagement/Form', [
            'data'       => $data,
            'isEdit'     => true,
            'categories' => $categories,
        ]);
    }

    public function update(NewsManagementRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return redirect()->route('admin.news-management.index')
                         ->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->service->destroy($id);

        return redirect()->route('admin.news-management.index')
                         ->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:news,id',
        ]);

        $result = $this->service->toggleActive($validated['id']);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換首頁曝光
     */
    public function toggleHomepageFeatured(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:news,id',
        ]);

        $result = $this->service->toggleHomepageFeatured($validated['id']);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換置頂
     */
    public function togglePinned(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:news,id',
        ]);

        $result = $this->service->togglePinned($validated['id']);

        return redirect()->back()->with('result', $result);
    }
}
