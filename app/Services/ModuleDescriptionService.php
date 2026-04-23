<?php
// app/Service/ModuleDescriptionService.php

namespace App\Services;

use App\Repositories\ModuleDescriptionRepository;
use App\Services\BaseService;
use App\Services\EventService;
use Illuminate\Support\Facades\Cache;

class ModuleDescriptionService extends BaseService
{
    protected $moduleDescriptionRepository;
    protected $eventService;

    public function __construct(
        ModuleDescriptionRepository $moduleDescriptionRepository,
        EventService $eventService
    ) {
        $this->moduleDescriptionRepository = $moduleDescriptionRepository;
        $this->eventService = $eventService;
    }

    /**
     * 取得 DataTable 資料
     *
     * @param mixed $perPage
     * @param string $sortColumn
     * @param string $sortDirection
     * @param array $filters
     * @return mixed
     */
    public function getDataTableData($perPage, $sortColumn, $sortDirection, $filters)
    {
        return $this->moduleDescriptionRepository->paginate($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 取得編輯資料
     *
     * @param int $id
     * @return array
     */
    public function getEditData($id)
    {
        $moduleDescription = $this->moduleDescriptionRepository->find($id);
        
        if (!$moduleDescription) {
            throw new \Exception('找不到該模組描述');
        }
        
        return [
            'id' => $moduleDescription->id,
            'module_key' => $moduleDescription->module_key,
            'meta_description' => [
                'zh_TW' => $moduleDescription->getTranslation('meta_description', 'zh_TW'),
                'en' => $moduleDescription->getTranslation('meta_description', 'en'),
            ],
            'meta_keywords' => [
                'zh_TW' => $moduleDescription->getTranslation('meta_keywords', 'zh_TW'),
                'en' => $moduleDescription->getTranslation('meta_keywords', 'en'),
            ],
        ];
    }

    /**
     * 儲存模組描述
     *
     * @param array $data
     * @param int|null $id
     * @return array
     */
    public function save(array $data, $id = null)
    {
        try {
            $moduleDescription = $this->moduleDescriptionRepository->save($data, $id);
            
            // 記錄操作日誌（使用 Model 的 event_title）
            $moduleDescription->event_title = ($id ? '更新' : '新增') . '模組描述：' . $moduleDescription->module_name;
            
            if ($id) {
                $this->eventService->fireDataUpdated($moduleDescription);
            } else {
                $this->eventService->fireDataCreated($moduleDescription);
            }
            
            // 清除快取
            $this->clearCache();
            
            // 新增完成後返回列表，編輯完成後留在原頁
            $redirect = $id ? '' : route('admin.module-descriptions');
            $message = $id ? '更新成功' : '新增成功';
            
            return $this->ReturnHandle(true, $message, $redirect);
            
        } catch (\Exception $e) {
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 刪除模組描述
     *
     * @param int $id
     * @return array
     */
    public function delete($id)
    {
        try {
            $moduleDescription = $this->moduleDescriptionRepository->find($id);
            
            if (!$moduleDescription) {
                throw new \Exception('找不到該模組描述');
            }
            
            $result = $this->moduleDescriptionRepository->delete($id);
            
            if ($result) {
                // 記錄操作日誌
                $moduleDescription->event_title = '刪除模組描述：' . $moduleDescription->module_name;
                $this->eventService->fireDataDeleted($moduleDescription);
                
                // 清除快取
                $this->clearCache();
                
                return $this->ReturnHandle(true, '刪除成功');
            }
            
            throw new \Exception('刪除失敗');
            
        } catch (\Exception $e) {
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 取得前台所有模組描述（含快取）
     *
     * @return array
     */
    public function getFrontendModuleDescriptions()
    {
        $cacheKey = 'frontend_module_descriptions_' . app()->getLocale();
        
        return Cache::remember($cacheKey, 3600, function () {
            $modules = $this->moduleDescriptionRepository->getAllForFrontend();
            
            $result = [];
            foreach ($modules as $module) {
                $result[$module->module_key] = [
                    'title' => $module->module_name, // 使用 Model 的 module_name accessor
                    'description' => $module->getTranslation('meta_description', app()->getLocale()),
                    'keywords' => $module->getTranslation('meta_keywords', app()->getLocale()),
                ];
            }
            
            return $result;
        });
    }

    /**
     * 根據 module_key 取得模組描述
     *
     * @param string $moduleKey
     * @return array|null
     */
    public function getByModuleKey(string $moduleKey)
    {
        $allModules = $this->getFrontendModuleDescriptions();
        return $allModules[$moduleKey] ?? null;
    }

    /**
     * 清除快取
     */
    private function clearCache()
    {
        Cache::forget('frontend_module_descriptions_zh_TW');
        Cache::forget('frontend_module_descriptions_en');
    }
}