<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModuleDescription\ModuleDescriptionRequest;
use App\Services\ModuleDescriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ModuleDescription;

class ModuleDescriptionController extends Controller
{
    public function __construct(
        private ModuleDescriptionService $moduleDescriptionService
    ) {
    }
    /**
     * 列表頁面
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'updated_at';
        $sortDirection = $request->input('sortDirection') ?? 'desc';
        $perPage = $request->input('length') ?? '10';

        $moduleDescriptions = $this->moduleDescriptionService->getDataTableData($perPage, $sortColumn, $sortDirection, $filters);
        return Inertia::render('Admin/ModuleDescription/Index', compact('moduleDescriptions'));
    }

    /**
     * 新增頁面
     */
    public function create()
    {
        return Inertia::render('Admin/ModuleDescription/Form', [
            'moduleKeys' => config('module_keys')
        ]);
    }

    /**
     * 儲存新增
     */
    public function store(ModuleDescriptionRequest $request)
    {
        $result = $this->moduleDescriptionService->save($request->validated());
        
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 編輯頁面
     */
    public function edit($id)
    {
        $moduleDescription = $this->moduleDescriptionService->getEditData($id);
        
        return Inertia::render('Admin/ModuleDescription/Form', [
            'moduleDescription' => $moduleDescription,
            'moduleKeys' => config('module_keys')
        ]);
    }

    /**
     * 顯示頁面
     */
    public function show($id)
    {
        return $this->edit($id);
    }

    /**
     * 更新資料
     */
    public function update(ModuleDescriptionRequest $request, $id)
    {
        $result = $this->moduleDescriptionService->save($request->validated(), $id);
        
        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 刪除資料
     */
    public function destroy($id)
    {
        $result = $this->moduleDescriptionService->delete($id);
        
        return redirect()
            ->back()
            ->with('result', $result);
    }
}
