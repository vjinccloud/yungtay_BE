<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Services\EventService;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomPermissionException;
use App\Exceptions\BusinessException;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Cache;
class BaseService
{
    use CommonTrait;
    protected $repository;
    protected $eventService;
    protected $imageRepository;

    // 排序相關設定（子類別可覆寫）
    protected $autoNormalizeAfterDelete = false;  // 刪除後是否自動重整排序
    protected $sortColumn = null;                 // 排序欄位名稱（null 表示使用 repository 預設）

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
        $this->eventService =  app(EventService::class);
        $this->imageRepository = app(ImageRepository::class);
    }

    public function  paginate($perPage, $sortColumn = 'null', $sortDirection = 'null',$filters){
        return $this->repository->paginate($perPage,$sortColumn, $sortDirection,$filters);
    }


    public function add(array $attributes = [])
    {
        return $this->repository->create($attributes);
    }

    public function all()
    {
        return $this->repository->all();
    }


    /**
     * 根据指定的ID查找模型实例
     *
     * @param  mixed $id 模型的主键ID
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find($id)
    {
        return $this->repository->find($id);
    }


    /**
     * 尝试根据给定的ID查找模型实例。
     * 如果存在，则返回该实例；如果不存在，则创建一个新的B实例但不持久化到数据库。
     *
     * @param mixed $id 要查找的模型的主键ID。
     * @return \Illuminate\Database\Eloquent\Model 返回模型实例或新的模型对象。
     */
    public function findOrNew($id)
    {
        return $this->repository->findOrNew($id);
    }

    /**
     * 删除指定ID的模型
     *
     * @param mixed $id 模型主键ID。
     * @return array 回傳成功或失败的訊息。
     */
    public function delete($id)
    {
        try {
            if (empty($id)) {
                throw new \InvalidArgumentException('必須提供要刪除的資料 ID');
            }

            DB::beginTransaction();

            $model = $this->find($id);
            if (!$model) {
                throw new \Exception('查無資料');
            }

            // 新增：如果需要刪除圖片，子類別可以覆寫 hasImages() 方法
            if ($this->hasImages($model)) {
                $this->deleteRelatedImages($model);
            }

            $this->repository->delete($id);

            DB::commit();

            $this->eventService->fireDataDeleted($model);

            // 刪除成功後，檢查是否需要重整排序
            if ($this->autoNormalizeAfterDelete) {
                try {
                    $this->repository->normalizeSortOrders($this->sortColumn, true);
                } catch (\Exception $e) {
                    \Log::error('Failed to normalize sort after delete', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $return = $this->ReturnHandle(true, '刪除成功');

        } catch (CustomPermissionException $e) {
            DB::rollBack();
            throw $e;
        } catch (BusinessException $e) {
            // 業務邏輯錯誤 - 顯示給用戶
            DB::rollBack();
            return $this->ReturnHandle(false, $e->getMessage());
        } catch (\Exception $e) {
            // 系統錯誤 - 只記錄 log，顯示通用錯誤訊息
            DB::rollBack();
            \Log::error('刪除失敗', [
                'model' => $this->repository ? get_class($this->repository->getModel()) : 'unknown',
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ReturnHandle(false, '系統錯誤，請稍後再試或聯繫管理員');
        }

        return $return;
    }


    /**
     * 切換指定 模型 的狀態。
     * 如果目前狀態是啟用（1），則將其變更為停用（0），反之亦然。
     *
     * @param mixed $id 模型的主鍵ID。
     * @return array 回傳一個包含操作結果的陣列，包括成功或失敗的訊息。
     * @throws \Exception 當查無資料或其他異常發生時拋出。
     */
    public function checkedStatus($id, $statusField=null)
    {
        try {
            DB::beginTransaction();
            $model = $this->find($id);
            if(!$model)
                throw new \Exception('查無資料');

            // 自動偵測狀態欄位
            $statusField = $statusField ?: $this->getStatusField($model);

            // 切換狀態
            $currentStatus = $model->$statusField;
            $newStatus = $currentStatus == 1 ? 0 : 1;
            $model->$statusField = $newStatus;
            $model->save();
            // 設定通用的 event_type
            $this->setStatusChangeEventType($model);
            $this->eventService->fireDataChangeStatus($model);
            DB::commit();
            $return = $this->ReturnHandle(true, '修改成功');
        } catch (CustomPermissionException $e) {
            DB::rollBack();
            $return = $this->ReturnHandle(false, $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return $return;
    }


    /**
     * 自動偵測模型的狀態欄位名稱
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return string
     */
    protected function getStatusField($model)
    {
        // 常見的狀態欄位名稱列表（按優先順序）
        $possibleFields = ['is_active', 'status', 'active', 'enabled', 'is_enabled'];

        // 檢查模型是否有這些欄位
        foreach ($possibleFields as $field) {
            if ($model->hasAttribute($field)) {
                return $field;
            }
        }

        // 如果都沒有，預設使用 is_active
        return 'is_active';
    }

    // 預設實作，各 Service 可以覆寫
    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $eventTitle = $model->event_title;
        $model->event_type = $action.$eventTitle;
    }

    /**
     * 根据给定的 ID 数组重新排序 模型。
     *
     * @param array $ids 要排序的 模型记录的 ID 数组。
     * @return array 返回一個包含操作結果的數組
     */
    public function sort($ids){
        $this->repository->sort($ids);
        $return = $this->ReturnHandle(true,'排序成功');
        return  $return;
    }


       /**
     * 保存模型实例。
     * 如果提供了 ID，则更新对应的模型；如果未提供，则创建新模型。
     *
     * @param array $attributes 要保存的数据。
     * @param int|null $id 可选的模型 ID，用于更新现有记录。
     * @return \Illuminate\Database\Eloquent\Model 返回保存后的模型实例。
     * @throws \Exception 当操作失败时抛出异常。
     */
    public function save(array $attributes, $id = null)
    {
        try {
            DB::beginTransaction();
            $model = $this->repository->save($attributes,$id);
            DB::commit();
            $mag = $id ?'修改成功':'新增成功';
            if (is_null($id)) {
                $this->eventService->fireDataCreated($model);
            } else {
                $this->eventService->fireDataUpdated($model);
            }
            $return = $this->ReturnHandle(true,$mag);
        } catch (\Exception $e) {
            DB::rollBack();
            //$return=$this->ReturnHandle(false,$e->getMessage());
            throw $e;
        }
        return $return;
    }

    /**
     * 根據單一條件搜尋第一筆記錄
     *
     * @param string $column 欄位名稱
     * @param mixed $value 搜尋值
     * @param string $operator 比較運算子
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findBy($column, $value, $operator = '=')
    {
        return $this->repository->findBy($column, $value, $operator);
    }

    /**
     * 根據多個條件搜尋第一筆記錄
     *
     * @param array $conditions 搜尋條件
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findWhere(array $conditions)
    {
        return $this->repository->findWhere($conditions);
    }

    /**
     * 根據條件搜尋所有記錄
     *
     * @param array $conditions 搜尋條件
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWhere(array $conditions)
    {
        return $this->repository->getWhere($conditions);
    }

    // 3. 新增：檢查模型是否有圖片 (預設為 false，子類別可覆寫)
    protected function hasImages($model)
    {
        return false; // 預設沒有圖片，需要圖片功能的子類別覆寫此方法
    }

    // 修改 deleteRelatedImages 方法
    protected function deleteRelatedImages($model)
    {
        try {
            // 使用 repository 的方法取得所有圖片
            $images = $this->repository->getAllImages($model);

            if ($images && $images->count() > 0) {
                foreach ($images as $image) {
                    // 刪除實體檔案
                    $this->imageRepository->deleteImgFile($image);
                    // 刪除資料庫記錄
                    $this->imageRepository->deleteImg($image);
                }
            }
        } catch (\Exception $e) {
            \Log::warning("刪除圖片時發生錯誤: " . $e->getMessage(), [
                'model_class' => get_class($model),
                'model_id' => $model->id ?? 'unknown'
            ]);
        }
    }

    // 4. 新增：根據圖片類型刪除相關圖片
    protected function deleteImagesByType($model, $imageTypes)
    {
        try {
            // 使用 repository 的方法取得特定類型的圖片
            $images = $this->repository->getImagesByType($model, $imageTypes);

            foreach ($images as $image) {
                $this->imageRepository->deleteImgFile($image);
                $this->imageRepository->deleteImg($image);
            }
        } catch (\Exception $e) {
            \Log::warning("根據類型刪除圖片時發生錯誤: " . $e->getMessage(), [
                'model_class' => get_class($model),
                'model_id' => $model->id ?? 'unknown',
                'image_types' => $imageTypes
            ]);
        }
    }

    // 5. 新增：批量刪除 (包含圖片處理)
    public function batchDelete(array $ids)
    {
        try {
            if (empty($ids)) {
                throw new \InvalidArgumentException('必須提供要刪除的資料 ID');
            }

            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($ids as $id) {
                try {
                    $model = $this->find($id);
                    if ($model) {
                        // 如果有圖片才刪除
                        if ($this->hasImages($model)) {
                            $this->deleteRelatedImages($model);
                        }
                        // 刪除主要資料
                        $this->repository->delete($id);
                        $this->eventService->fireDataDeleted($model);
                        $deletedCount++;
                    } else {
                        $errors[] = "ID {$id}: 資料不存在"; // 新增：記錄不存在的資料
                    }
                } catch (\Exception $e) {
                    $errors[] = "ID {$id}: " . $e->getMessage();
                    // 如果是重要錯誤，可以考慮立即中斷
                    // break;
                }
            }

            if (count($errors) > 0) {
                DB::rollBack();
                return $this->ReturnHandle(false, '批量刪除失敗', [
                    'deleted_count' => 0, // 回滾後實際刪除數為 0
                    'errors' => $errors
                ]);
            }

            DB::commit();

            // 批次刪除成功後，檢查是否需要重整排序
            if ($this->autoNormalizeAfterDelete && $deletedCount > 0) {
                try {
                    $this->repository->normalizeSortOrders($this->sortColumn, true);
                } catch (\Exception $e) {
                    \Log::error('Failed to normalize sort after batch delete', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $this->ReturnHandle(true, "成功刪除 {$deletedCount} 筆資料");

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getModuleTitle()
    {
        return '資料'; // 預設標題
    }

    /**
     * 取得排序事件標題
     * 子類別可覆寫此方法來提供更具體的標題
     *
     * @param mixed $model
     * @return string
     */
    protected function getSortEventTitle($model)
    {
        // 直接使用模組名稱，不使用 event_title（避免重複格式）
        // EventService 會自動在後面加上 "排序調整"
        $moduleTitle = $this->getModuleTitle();
        return $moduleTitle;
    }

    /**
     * 前台取得列表
     */
    public function getFrontendList($perPage = 6, $search = null)
    {
        return $this->repository->getFrontendList($perPage, $search);
    }

    /**
     * 前台取得單一詳情
     */
    public function getFrontendDetail($id)
    {
        return $this->repository->getFrontendDetail($id);
    }

    // ================== 排序功能 ==================

    /**
     * 更新排序
     *
     * @param array $sortData 排序資料
     * @param string|null $column 排序欄位名稱
     * @return array
     */
    public function updateSort(array $sortData, string $column = null)
    {
        try {
            $this->repository->batchUpdateSort($sortData, $column);

            // 觸發排序事件
            if ($this->eventService && !empty($sortData)) {
                // 取得第一筆資料作為事件主體
                $firstId = $sortData[0]['id'] ?? null;
                if ($firstId) {
                    $model = $this->repository->find($firstId);
                    if ($model) {
                        // 提取所有 ID
                        $ids = array_column($sortData, 'id');
                        // 生成更具體的事件標題：[編輯]影音分類排序調整
                        $modelTitle = $this->getSortEventTitle($model);
                        $this->eventService->fireDataSort($model, $ids, $modelTitle);
                    }
                }
            }

            return $this->returnHandle(true, '排序更新成功');
        } catch (\Exception $e) {
            \Log::error('Sort update failed', [
                'error' => $e->getMessage(),
                'data' => $sortData
            ]);

            return $this->returnHandle(
                false,
                '排序更新失敗：' . $e->getMessage()
            );
        }
    }

    /**
     * 清除首頁快取
     * 當影響首頁顯示的內容更新時呼叫
     */
    protected function clearHomePageCache()
    {
        $locales = ['zh_TW', 'en'];
        foreach ($locales as $locale) {
            Cache::forget("homepage_data_{$locale}");
        }
    }

    /**
     * 取得模組 SEO 資料
     */
    public function getModuleSEO($moduleKey)
    {
        $moduleDescRepository = app(\App\Repositories\ModuleDescriptionRepository::class);
        $moduleData = $moduleDescRepository->findByModuleKey($moduleKey);

        if ($moduleData) {
            $locale = app()->getLocale();
            return [
                'description' => $moduleData->getTranslation('meta_description', $locale) ?? '',
            ];
        }
        return [
            'description' => null,
        ];
    }
}
