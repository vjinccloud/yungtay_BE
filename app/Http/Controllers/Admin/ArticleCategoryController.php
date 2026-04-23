<?php

namespace App\Http\Controllers\Admin;

use App\Services\CategoryService;

class ArticleCategoryController extends CategoryController
{
    public function __construct(CategoryService $categories)
    {
        parent::__construct($categories);

        // 只賦值，不重複宣告
        $this->categoryType       = 'article';
        $this->categoryTitle      = '新聞分類';
        $this->allowSubcategories = false;  // 新聞不允許子分類
        $this->maxLevel           = 0;      // 只有一層
    }
}