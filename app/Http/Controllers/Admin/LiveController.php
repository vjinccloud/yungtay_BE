<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Live;
use App\Http\Requests\Live\LiveRequest;
use App\Services\LiveService;

class LiveController extends Controller
{
    public function __construct(
        private LiveService $liveService,
    ) {
    }

    /**
     * 顯示直播列表
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'sort_order';
        $sortDirection = $request->input('sortDirection') ?? 'asc';
        $perPage = $request->input('length') ?? '10';

        $lives = $this->liveService->paginate($perPage, $sortColumn, $sortDirection, $filters);
        
        return Inertia::render('Admin/Live/Index', compact('lives'));
    }

    /**
     * 顯示新增表單
     */
    public function create()
    {
        return Inertia::render('Admin/Live/Form');
    }

    /**
     * 儲存新增的直播
     */
    public function store(LiveRequest $request)
    {
        $validated = $request->validated();
        $result = $this->liveService->save($validated);
        
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 顯示單一直播（重導向到編輯）
     */
    public function show($id)
    {
        return redirect()->route('admin.lives.edit', $id);
    }

    /**
     * 顯示編輯表單
     */
    public function edit(string $id)
    {
        $live = $this->liveService->getEditData($id);
        
        return Inertia::render('Admin/Live/Form', [
            'live' => $live
        ]);
    }

    /**
     * 更新直播
     */
    public function update(LiveRequest $request, string $id)
    {
        $validated = $request->validated();
        $result = $this->liveService->save($validated, $id);
        
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 刪除直播
     */
    public function destroy($id)
    {
        $result = $this->liveService->delete($id);
        
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:lives,id',
        ]);

        $result = $this->liveService->checkedStatus($validated['id']);
        
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 更新排序
     */
    public function sort(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:lives,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        $result = $this->liveService->updateSort($validated['items']);
        
        return redirect()
            ->back()
            ->with('result', $result);
    }
}
