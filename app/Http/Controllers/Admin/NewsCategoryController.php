<?php

namespace App\Http\Controllers\Admin;

use App\Services\CategoryService;

class NewsCategoryController extends CategoryController
{
    public function __construct(CategoryService $categories)
    {
        parent::__construct($categories);

        // 只賦值，不重複宣告
        $this->categoryType         = 'news';
        $this->categoryTitle        = '最新消息分類';
        $this->allowSubcategories   = false;  // 最新消息不允許子分類
        $this->requireSubcategories = false;  // 子分類非必填
        $this->maxLevel             = 0;      // 只有一層
    }
}
