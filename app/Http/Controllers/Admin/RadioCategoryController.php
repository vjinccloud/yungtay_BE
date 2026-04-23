<?php

namespace App\Http\Controllers\Admin;

use App\Services\CategoryService;

class RadioCategoryController extends CategoryController
{
    public function __construct(CategoryService $categories)
    {
        parent::__construct($categories);

        // 只賦值，不重複宣告
        $this->categoryType        = 'radio';
        $this->categoryTitle       = '廣播分類';
        $this->allowSubcategories  = true;   // 廣播允許子分類
        $this->requireSubcategories = false; // 子分類非必填
        $this->maxLevel            = 1;      // 允許兩層（主分類 + 子分類）
    }
}
