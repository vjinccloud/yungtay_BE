<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;

class CategoryService extends BaseService
{
    // 啟用刪除後自動重整排序（分類使用 seq 欄位）
    protected $autoNormalizeAfterDelete = true;
    protected $sortColumn = 'seq';

    public function __construct(
        private CategoryRepository $category,
    ) {
        parent::__construct($category);
        $this->eventService =  app(EventService::class);
    }
    /**
     * 儲存影音資料（新增或更新）
     *
     * @param array $data 影音資料
     * @param int|null $id 影音 ID（更新時使用）
     * @return array
     */
    public function save(array $data, $id = null)
    {
        try {
            DB::beginTransaction();
            $category = $this->repository->save($data, $id);
            DB::commit();

            // 觸發對應的事件
            if (is_null($id)) {
                // 新增影音
                $this->eventService->fireDataCreated($category);
                $message = '新增成功';
            } else {
                // 編輯影音
                $this->eventService->fireDataUpdated($category);
                $message = '更新成功';
            }

            $result = $this->ReturnHandle(true, $message);
            if (is_null($id)) {
                // 新增成功後導向編輯頁，方便繼續編輯子分類
                $categoryType = $data['type'] ?? 'drama';
                $routePrefix = 'admin.' . str_replace('_', '-', $categoryType) . '-categories';
                $result['redirect'] = route($routePrefix . '.edit', $category->id);
            }

            return $result;
        } catch (\Exception $e) {
            $id = $id ?? null;
            // 根據分類類型動態決定路由
            $categoryType = $data['type'] ?? 'drama';
            $routePrefix = 'admin.' . str_replace('_', '-', $categoryType) . '-categories';
            $redirect = $id ? route($routePrefix . '.edit', $id) : route($routePrefix . '.add');

            DB::rollBack();
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    public function getDataTableData($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->paginate($perPage, $sortColumn, $sortDirection, $filters);
    }
    /**
     * 取得新增表單需要的資料
     */
    public function getCreateData($type)
    {
        return [
            'categories' => $this->category->getCategoryTree($type),
            'nextSeq' => $this->category->getNextSeq($type),
        ];
    }

    /**
     * 排序 - 使用 BaseService 的 updateSort
     */
    public function sort($ids)
    {
        // 格式轉換：純 ID 陣列轉為物件陣列
        $sortData = array_map(fn($id) => ['id' => $id], $ids);

        // 使用 BaseService 的 updateSort（已包含事件處理）
        return parent::updateSort($sortData, 'seq');
    }

    /**
     * 覆寫模組標題 - 根據分類類型生成具體標題
     */
    protected function getModuleTitle()
    {
        return '分類'; // 簡化為統一的分類
    }

    /**
     * 覆寫排序事件標題 - 根據分類類型顯示具體名稱
     */
    protected function getSortEventTitle($model)
    {
        // 如果是集合（批次排序），取第一個的類型
        if ($model instanceof \Illuminate\Support\Collection) {
            $firstItem = $model->first();
            if ($firstItem && isset($firstItem->type)) {
                return \App\Models\Category::getTypeTitle($firstItem->type);
            }
        }
        // 如果是單一模型
        elseif ($model && isset($model->type)) {
            return \App\Models\Category::getTypeTitle($model->type);
        }

        // 預設返回
        return '分類';
    }


    /**
     * 取得編輯表單需要的資料
     *
     * @param int $id 分類 ID
     * @param string $type 分類類型（用於驗證）
     * @return array|null
     */
    public function getEditData($id, $type = null)
    {
        $categoryData = $this->category->getEditFormData($id);
        return  $categoryData;
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->status == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('name', 'zh_TW') ?? '';
        $model->event_status_title = $model->getTypeTitle($model->type) . "{$action}-{$title}";
    }


    /**
     * 取得指定類型的分類資料（用於表單選項）
     *
     * @param string $type 分類類型 (drama, program, radio)
     * @return array
     */
    public function getCategoriesForForm($type)
    {
        try {
            return $this->category->getCategoriesWithSubcategories($type);
        } catch (\Exception $e) {
            \Log::error("取得 {$type} 分類資料失敗: " . $e->getMessage());

            // 發生錯誤時回傳空資料結構
            return [
                'main' => collect([]),
                'sub' => collect([])
            ];
        }
    }


    /**
     * 取得影音分類資料
     *
     * @return array
     */
    public function getDramaCategories()
    {
        return $this->getCategoriesForForm('drama');
    }

    /**
     * 取得節目分類資料
     *
     * @return array
     */
    public function getProgramCategories()
    {
        return $this->getCategoriesForForm('program');
    }

    /**
     * 取得廣播分類資料
     *
     * @return array
     */
    public function getRadioCategories()
    {
        return $this->getCategoriesForForm('radio');
    }

    /**
     * 取得新聞分類資料
     *
     * @param bool $forFrontend 是否為前台使用（前台只顯示有文章的分類）
     * @return array
     */
    public function getArticleCategories($forFrontend = false)
    {
        if ($forFrontend) {
            // 前台：只顯示有文章的分類
            $categories = $this->category->getCategoriesWithArticles();
        } else {
            // 後台：顯示所有啟用的分類
            $categories = $this->category->getAllArticleCategories();
        }

        return [
            'main' => $categories,
            'sub' => collect([]) // 保持與原有格式一致，但不提供子分類
        ];
    }

    /**
     * 刪除子分類
     *
     * @param int $childId 子分類 ID
     * @return array
     */
    public function deleteChild($childId)
    {
        try {
            $this->category->deleteChild($childId);
            return $this->ReturnHandle(true, '子分類刪除成功');
        } catch (\Exception $e) {
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 依類型取得分類列表（用於下拉選單）
     *
     * @param string $type 分類類型 (news, drama, program, radio, article)
     * @return \Illuminate\Support\Collection
     */
    public function getCategoriesByType($type)
    {
        return $this->category->getCategoriesByType($type);
    }
}
