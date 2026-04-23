<?php
// app/Http/Controllers/Admin/ProgramCategoryController.php
namespace App\Http\Controllers\Admin;

use App\Services\CategoryService;

class ProgramCategoryController extends CategoryController
{
    public function __construct(CategoryService $categories)
    {
        parent::__construct($categories);

        // 只賦值，不重複宣告
        $this->categoryType       = 'program';
        $this->categoryTitle      = '節目分類';
        $this->allowSubcategories = true;
        $this->maxLevel           = 1;
    }
}