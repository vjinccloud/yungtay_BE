<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\BannerService;
use App\Http\Requests\Banner\BannerRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BannerController extends Controller
{
    protected BannerService $bannerService;

    public function __construct(BannerService $bannerService)
    {
        $this->bannerService = $bannerService;
    }

    /**
     * 顯示輪播圖列表
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order';
        $sortDirection = $request->input('sortDirection') ?? 'asc';
        $perPage = $request->input('length') ?? '10';

        $banners = $this->bannerService->paginate($perPage, $sortColumn, $sortDirection, $filters);

        return Inertia::render('Admin/Banner/Index', compact('banners'));
    }

    /**
     * 顯示新增表單
     */
    public function create()
    {
        return Inertia::render('Admin/Banner/Form');
    }

    /**
     * 儲存新輪播圖
     */
    public function store(BannerRequest $request)
    {
        $validated = $request->validated();
        $result = $this->bannerService->save($validated);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 顯示編輯表單
     */
    public function edit($id)
    {
        $banner = $this->bannerService->getFormData($id);
        
        return Inertia::render('Admin/Banner/Form', [
            'banner' => $banner
        ]);
    }

    /**
     * 更新輪播圖
     */
    public function update(BannerRequest $request, $id)
    {
        $validated = $request->validated();
        $result = $this->bannerService->save($validated, $id);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 刪除輪播圖
     */
    public function destroy($id)
    {
        $result = $this->bannerService->delete($id);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:banners,id',
        ]);

        $result = $this->bannerService->checkedStatus($validated['id']);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 更新排序
     */
    public function sort(Request $request)
    {
        $validated = $request->validate([
            'sorted' => 'required|array',
            'sorted.*.id' => 'required|integer|exists:banners,id',
            'sorted.*.sort_order' => 'required|integer|min:0'
        ]);

        $result = $this->bannerService->updateSort($validated['sorted']);
        return redirect()->back()->with('result', $result);
    }
}
