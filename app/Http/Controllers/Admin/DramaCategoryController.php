<?php
// app/Http/Controllers/Admin/DramaCategoryController.php
namespace App\Http\Controllers\Admin;

use App\Services\CategoryService;

class DramaCategoryController extends CategoryController
{
    public function __construct(CategoryService $categories)
    {
        parent::__construct($categories);

        // 只賦值，不重複宣告
        $this->categoryType       = 'drama';
        $this->categoryTitle      = '影音分類';
        $this->allowSubcategories = true;
        $this->maxLevel           = 1;
    }
}
// app/Http/Controllers/Admin/RadioCategoryController.php
// namespace App\Http\Controllers\Admin;

// class RadioCategoryController extends CategoryController
// {
//     public function __construct()
//     {
//         $this->categoryType = 'radio';
//         $this->categoryTitle = '廣播分類';
//         $this->allowSubcategories = false;  // 不允許子分類
//         $this->maxLevel = 0;  // 只有一層
//     }
// }
