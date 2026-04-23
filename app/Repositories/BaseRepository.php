<?php

namespace App\Repositories;

use App\Traits\CommonTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    use CommonTrait;
    protected $model;
    
    // 排序相關設定（子類別可覆寫）
    protected $sortColumn = 'sort_order';  // 預設排序欄位名稱
    protected $sortGap = 1;                 // 預設排序間隔（連續排序）
    protected $maxGapThreshold = 100;       // 最大間隙閾值（適合小量資料）
    protected $minGapThreshold = 1;         // 最小間隙閾值

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * 取得 Model 實例
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function create(array $attributes = [])
    {
        return $this->model->create($attributes);
    }

    public function update($id, array $attributes)
    {
        $record = $this->find($id);
        if ($record) {
            $record->update($attributes);
            return $record;
        }
        return null;
    }

    public function delete($id)
    {
        $record = $this->find($id);
        if ($record) {
            return $record->delete();
        }
        return false;
    }

    /**
     * 创建并返回一个新的模型实例。
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newInstance()
    {
        return new $this->model;
    }

    /**
     * 根據 ID 查找模型
     * 
     * @param int $id
     * @param bool $failIfNotFound 如果找不到是否拋出異常
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find($id, bool $failIfNotFound = false)
    {
        if ($failIfNotFound) {
            return $this->model->findOrFail($id);
        }
        return $this->model->newQuery()->find($id);
    }

    /**
     * 根據 ID 陣列查找多個模型
     *
     * @param array $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByIds(array $ids)
    {
        return $this->model->newQuery()->whereIn('id', $ids)->get();
    }

    /**
     * @deprecated 請使用 find($id, true) 代替
     */
    public function findModel(int $id = 1)
    {
        return $this->find($id, true);
    }

    /**
     * 尝试根据给定的ID查找模型实例。
     * 如果模型实例不存在，则创建一个新的模型实例但不持久化到数据库。
     *
     * @param mixed $id 要查找的模型的主键ID。
     * @return \Illuminate\Database\Eloquent\Model 返回模型实例或新的模型对象。
     */
    public function findOrNew($id)
    {
        return $this->find($id) ?? $this->newInstance();
    }

    /**
     * 根据给定的 ID 数组重新排序 Banner。
     *
     * @param array $ids 要排序的 Banner 记录的 ID 数组。
     * @return void
     */
    public function sort($ids)
    {
        foreach ($ids as $k => $itemId) {
            $seq = $k + 1;
            $this->model->where('id', $itemId)->update(['seq' => $seq]);
        }
    }

    /**
     * 保存模型实例。
     * 如果模型是新的，则会创建；如果是现有的，则会更新。
     *
     * @param array $attributes 要保存的数据。
     * @param int|null $id 可选的模型 ID，用于查找和更新现有记录。
     * @return \Illuminate\Database\Eloquent\Model 返回保存后的模型实例。
     */
    public function save(array $attributes, $id = null)
    {
        if ($id) {
            // 直接使用 model 查詢，避免依賴可能被覆寫的 find() 方法
            $model = $this->model->findOrFail($id);

            // 是否使用 fill/save 模式
            if ($this->shouldUseFill($model)) {
                $model->fill($attributes);
                $model->save();
                return $model;
            }

            $model->update($attributes);
            return $model;
        }

        return $this->model->create($attributes);
    }

    protected function shouldUseFill(Model $model): bool
    {
        // 自動判斷是否有使用 spatie 的 HasTranslations trait
        return in_array(
            \Spatie\Translatable\HasTranslations::class,
            class_uses_recursive($model)
        );
    }

    // ================== 新增搜尋功能 ==================

    /**
     * 根據單一條件搜尋第一筆記錄
     *
     * @param string $column 欄位名稱
     * @param mixed $value 搜尋值
     * @param string $operator 比較運算子 (=, >, <, like 等)
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findBy($column, $value, $operator = '=')
    {
        return $this->model->newQuery()->where($column, $operator, $value)->first();
    }

    /**
     * 根據多個條件搜尋第一筆記錄
     *
     * @param array $conditions 搜尋條件 ['column' => 'value'] 或 ['column', 'operator', 'value']
     * @return \Illuminate\Database\Eloquent\Model|null
     *
     * 使用範例：
     * $repo->findWhere(['email' => 'test@example.com', 'status' => 1]);
     * $repo->findWhere([['name', 'like', '%test%'], ['status', '=', 1]]);
     */
    public function findWhere(array $conditions)
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $key => $condition) {
            if (is_array($condition) && count($condition) === 3) {
                // ['column', 'operator', 'value'] 格式
                $query->where($condition[0], $condition[1], $condition[2]);
            } else {
                // ['column' => 'value'] 格式
                $query->where($key, $condition);
            }
        }

        return $query->first();
    }

    /**
     * 根據條件搜尋所有記錄
     *
     * @param array $conditions 搜尋條件
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWhere(array $conditions)
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $key => $condition) {
            if (is_array($condition) && count($condition) === 3) {
                $query->where($condition[0], $condition[1], $condition[2]);
            } else {
                $query->where($key, $condition);
            }
        }

        return $query->get();
    }

    /**
     * 根據條件搜尋並分頁
     *
     * @param array $conditions 搜尋條件
     * @param int $perPage 每頁筆數
     * @param array $columns 要選擇的欄位
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWhere(array $conditions, $perPage = 15, $columns = ['*'])
    {
        $query = $this->model->newQuery();

        foreach ($conditions as $key => $condition) {
            if (is_array($condition) && count($condition) === 3) {
                $query->where($condition[0], $condition[1], $condition[2]);
            } else {
                $query->where($key, $condition);
            }
        }

        return $query->paginate($perPage, $columns);
    }

    /**
     * 使用自定義查詢建構器
     *
     * @param \Closure $callback 查詢建構器回調函數
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * 使用範例：
     * $repo->query(function($query) {
     *     return $query->where('status', 1)
     *                  ->where('created_at', '>', now()->subDays(30))
     *                  ->orderBy('created_at', 'desc');
     * });
     */
    public function query(\Closure $callback)
    {
        $query = $this->model->newQuery();
        return $callback($query)->get();
    }

    /**
     * 使用自定義查詢建構器並回傳第一筆
     *
     * @param \Closure $callback 查詢建構器回調函數
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function queryFirst(\Closure $callback)
    {
        $query = $this->model->newQuery();
        return $callback($query)->first();
    }


    /**
     * 模糊搜尋
     *
     * @param string $column 欄位名稱
     * @param string $value 搜尋值
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByLike($column, $value)
    {
        return $this->model->newQuery()->where($column, 'like', "%{$value}%")->get();
    }

    /**
     * 根據類型取得圖片
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string|array $imageTypes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getImagesByType($model, $imageTypes)
    {
        if (!method_exists($model, 'image')) {
            return collect();
        }

        $types = is_array($imageTypes) ? $imageTypes : [$imageTypes];

        return $model->image()->whereIn('image_type', $types)->get();
    }

    /**
     * 取得模型的所有圖片
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllImages($model)
    {
        if (!method_exists($model, 'image')) {
            return collect();
        }

        return $model->image()->get();
    }

    /**
     * 格式化標籤資料給前端使用
     *
     * @param string|null $tags 標籤字串
     * @return string 格式化後的標籤字串
     */
    public function formatTagsForFrontend($tags)
    {
        if (empty($tags)) {
            return '';
        }

        // 如果是 JSON 格式的陣列，轉換為逗號分隔的字串
        if (is_string($tags) && (str_starts_with($tags, '[') || str_starts_with($tags, '{'))) {
            try {
                $decoded = json_decode($tags, true);
                if (is_array($decoded)) {
                    return implode(',', $decoded);
                }
            } catch (\Exception $e) {
                // 如果解析失敗，直接返回原字串
            }
        }

        return $tags;
    }

    /**
     * 格式化標籤為陣列
     *
     * @param string|null $tags
     * @return array
     */
    public function formatTagsArray($tags)
    {
        if (empty($tags)) {
            return [];
        }

        // 如果是 JSON 格式
        if (is_string($tags) && (str_starts_with($tags, '[') || str_starts_with($tags, '{'))) {
            try {
                $decoded = json_decode($tags, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Exception $e) {
                // 解析失敗，繼續處理
            }
        }

        // 逗號分隔轉陣列
        return array_map('trim', explode(',', $tags));
    }
    
    // ================== 排序功能 ==================
    
    /**
     * 取得下一個排序值（用於新增時）
     * 
     * @param string|null $column 排序欄位名稱，null 則使用預設
     * @return int
     */
    public function getNextSortOrder(string $column = null): int
    {
        $column = $column ?: $this->sortColumn;
        $max = (int) $this->model->max($column);
        return $max + $this->sortGap;
    }
    
    /**
     * 檢查是否需要重新整理排序
     * 
     * @param string|null $column 排序欄位名稱
     * @return bool
     */
    public function needsSortNormalization(string $column = null): bool
    {
        $column = $column ?: $this->sortColumn;
        
        // 檢查是否有重複的排序值
        $hasDuplicates = DB::table($this->model->getTable())
            ->select($column, DB::raw('COUNT(*) as count'))
            ->whereNotNull($column)
            ->groupBy($column)
            ->having('count', '>', 1)
            ->exists();
        
        if ($hasDuplicates) {
            return true;
        }
        
        // 檢查間隙是否過大或過小
        $items = $this->model
            ->whereNotNull($column)
            ->orderBy($column, 'asc')
            ->pluck($column)
            ->toArray();
        
        if (count($items) <= 1) {
            return false;
        }
        
        $maxGap = 0;
        $minGap = PHP_INT_MAX;
        
        for ($i = 1; $i < count($items); $i++) {
            $gap = $items[$i] - $items[$i - 1];
            $maxGap = max($maxGap, $gap);
            $minGap = min($minGap, $gap);
        }
        
        // 如果最大間隙超過閾值或最小間隙小於 1，需要重整
        return $maxGap > $this->maxGapThreshold || $minGap < $this->minGapThreshold;
    }
    
    /**
     * 將所有記錄的排序欄位重新整理為使用間隔值
     * 
     * @param string|null $column 排序欄位名稱
     * @param bool $force 是否強制重整（不檢查是否需要）
     * @return void
     */
    public function normalizeSortOrders(string $column = null, bool $force = false): void
    {
        $column = $column ?: $this->sortColumn;
        
        // 如果不是強制重整，先檢查是否需要
        if (!$force && !$this->needsSortNormalization($column)) {
            return;
        }
        
        $table = $this->model->getTable();
        $items = $this->model
            ->whereNotNull($column)
            ->orderBy($column, 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', $column]);

        DB::beginTransaction();
        try {
            $index = 1;
            foreach ($items as $item) {
                $newSortOrder = $index * $this->sortGap;
                if ((int) $item->{$column} !== $newSortOrder) {
                    DB::table($table)->where('id', $item->id)->update([$column => $newSortOrder]);
                }
                $index++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * 批次更新排序（用於拖曳排序）
     * 
     * @param array $sortData 排序資料 [['id' => 1, 'order' => 1], ...]
     * @param string|null $column 排序欄位名稱
     * @return void
     */
    public function batchUpdateSort(array $sortData, string $column = null): void
    {
        $column = $column ?: $this->sortColumn;
        
        DB::beginTransaction();
        try {
            foreach ($sortData as $index => $item) {
                $sortValue = isset($item['order']) 
                    ? $item['order'] * $this->sortGap 
                    : ($index + 1) * $this->sortGap;
                    
                $this->update($item['id'], [
                    $column => $sortValue
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
