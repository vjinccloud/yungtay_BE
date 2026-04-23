<?php

namespace Modules\NewsCategoryManagement\Backend\Controller;

use App\Http\Controllers\Admin\CategoryController;
use App\Services\CategoryService;

class NewsCategoryManagementController extends CategoryController
{
    public function __construct(CategoryService $categories)
    {
        parent::__construct($categories);

        $this->categoryType        = 'news';
        $this->categoryTitle       = '最新消息分類';
        $this->allowSubcategories  = false;
        $this->requireSubcategories = false;
        $this->maxLevel            = 0;
    }
}
