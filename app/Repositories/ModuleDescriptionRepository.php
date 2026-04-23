<?php
// app/Repositories/ModuleDescriptionRepository.php

namespace App\Repositories;

use App\Models\ModuleDescription;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class ModuleDescriptionRepository extends BaseRepository
{
    public function __construct(ModuleDescription $moduleDescription)
    {
        parent::__construct($moduleDescription);
    }

    public function paginate($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        $query = $this->model->with(['created_user', 'updated_user']);

        // 搜尋
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('module_key', 'like', "%{$search}%")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta_description, '$.zh_TW')) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta_description, '$.en')) LIKE ?", ["%{$search}%"]);
            });
        }

        // 允許的排序欄位清單（安全檢查）
        $allowedSortColumns = ['id', 'module_key', 'created_at', 'updated_at'];
        
        // 如果排序欄位不在允許清單中，改用預設排序
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'updated_at';
        }

        return $query->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => $item->id,
                'module_key' => $item->module_key,
                'title' => $item->module_name, // 使用 Model 的 module_name accessor
                'meta_description_zh' => $item->getTranslation('meta_description', 'zh_TW'),
                'meta_description_en' => $item->getTranslation('meta_description', 'en'),
                'created_by' => $item->created_user?->name ?? '-',
                'updated_by' => $item->updated_user?->name ?? '-',
                'created_at' => $item->created_at?->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at?->format('Y-m-d H:i'),
            ]);
    }

    /**
     * 根據 module_key 取得模組描述
     *
     * @param string $moduleKey
     * @return ModuleDescription|null
     */
    public function findByModuleKey(string $moduleKey)
    {
        return $this->model->where('module_key', $moduleKey)->first();
    }

    /**
     * 儲存模組描述
     *
     * @param array $data
     * @param int|null $id
     * @return ModuleDescription
     */
    public function save(array $data, $id = null)
    {
        DB::beginTransaction();
        
        try {
            if ($id) {
                // 更新
                $moduleDescription = $this->model->findOrFail($id);
                $data['updated_by'] = auth('admin')->id();
                $moduleDescription->update($data);
            } else {
                // 新增
                $data['created_by'] = auth('admin')->id();
                $data['updated_by'] = auth('admin')->id();
                
                // module_key 由前端定義，不需要自動生成
                
                $moduleDescription = $this->model->create($data);
            }
            
            DB::commit();
            return $moduleDescription;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * 取得所有模組描述（用於前台）
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllForFrontend()
    {
        return $this->model->select([
            'module_key',
            'meta_description',
            'meta_keywords'
        ])->get();
    }

    /**
     * 刪除模組描述
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        try {
            $moduleDescription = $this->model->findOrFail($id);
            return $moduleDescription->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}