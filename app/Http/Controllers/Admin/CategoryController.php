<?php
// app/Http/Controllers/Admin/CategoryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\Category\CategoryRequest;
use App\Services\CategoryService;
abstract class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoriesService)
    {
    }

    // 在這裡一次宣告好，並給預設值
    protected string $categoryType       = '';
    protected string $categoryTitle      = '';
    protected bool   $allowSubcategories = true;
    protected bool   $requireSubcategories = true; // 子分類是否必填
    protected int    $maxLevel           = 1;

     /**
     * 顯示分類列表
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'seq'; // 默認排序列為 `id`
        $sortDirection = $request->input('sortDirection') ?? 'asc'; // 默認排序方向為升序
        $perPage =$request->input('length') ?? '1';
        // 強制篩選條件
        $filters['type'] = $this->categoryType;
        $filters['parent_id'] = null; // 只要主分類
        $category = $this->categoriesService->getDataTableData($perPage, $sortColumn, $sortDirection,$filters);

        return Inertia::render('Admin/Categories/Index', [
            'categoryType' => $this->categoryType,
            'categoryTitle' => $this->categoryTitle,
            'allowSubcategories' => $this->allowSubcategories,
            'maxLevel' => $this->maxLevel,
            'categories' => $category,
        ]);
    }

    public function create()
    {
        $data = $this->categoriesService->getCreateData($this->categoryType);

        return Inertia::render('Admin/Categories/Form', [
            'categories'    => $data['categories'],
            'categoryType'  => $this->categoryType,
            'categoryTitle' => $this->categoryTitle,
            'isEditing'     => false,
            'routeName'     => $this->getRoutePrefix(),
            'nextSeq'       => $data['nextSeq'],
            'allowSubcategories' => $this->allowSubcategories,
            'requireSubcategories' => $this->requireSubcategories,
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $validated = $request->validated();
        $result = $this->categoriesService->save($validated);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function edit($id)
    {
        $editData = $this->categoriesService->getEditData($id, $this->categoryType);

        $parentCategories = Category::ofType($this->categoryType)
            ->status(true)
            ->roots()
            ->where('id', '!=', $id)
            ->get();

        return Inertia::render('Admin/Categories/Form', [
            'category' => $editData,
            'parentCategories' => $parentCategories,
            'categoryType' => $this->categoryType,
            'categoryTitle' => $this->categoryTitle,
            'isEditing' => true,
            'routeName'     => $this->getRoutePrefix(),
            'allowSubcategories' => $this->allowSubcategories,
            'requireSubcategories' => $this->requireSubcategories,
        ]);
    }

    public function update(CategoryRequest $request, $id)
    {
        $validated = $request->validated();
        $result = $this->categoriesService->save($validated,$id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function destroy($id)
    {
        $result = $this->categoriesService->delete($id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function toggleActive(Request $request)
    {

        // 驗證請求
        $validated = $request->validate([
            'id' => 'required|exists:categories,id',
        ]);

        $result = $this->categoriesService->checkedStatus($validated['id']);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    protected function getRoutePrefix()
    {
        return 'admin.' . str_replace('_', '-', $this->categoryType) . '-categories';
    }

    /**
     * 排序
     */
    public function sort(Request $request)
    {
        $ids = $request->input('ids', []);
        $result = $this->categoriesService->sort($ids);

        return redirect()->back()->with('result', $result);
    }

    /**
     * 刪除子分類
     *
     * @param int $id 子分類 ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteChild($id)
    {
        $result = $this->categoriesService->deleteChild($id);

        return redirect()
            ->back()
            ->with('result', $result);
    }
}
