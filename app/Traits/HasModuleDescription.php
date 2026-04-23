<?php

namespace App\Traits;

use App\Services\ModuleDescriptionService;

/**
 * 提供模組描述功能給前台 Controller 使用
 */
trait HasModuleDescription
{
    /**
     * 取得模組的 SEO Meta 資訊
     *
     * @param string $moduleKey 模組鍵值 (如: 'news', 'drama', 'program')
     * @return array|null 包含 title, description, keywords 的陣列，或 null
     */
    protected function getModuleMeta(string $moduleKey): ?array
    {
        $moduleDescService = app(ModuleDescriptionService::class);
        $moduleDesc = $moduleDescService->getByModuleKey($moduleKey);
        
        if (!$moduleDesc) {
            return null;
        }
        
        // ModuleDescriptionService 已經處理語系，直接使用回傳值
        return [
            'meta_title' => $moduleDesc['title'] ?? '',
            'meta_description' => $moduleDesc['description'] ?? '',
            'meta_keywords' => $moduleDesc['keywords'] ?? '',
        ];
    }
    
    /**
     * 準備 SEO Meta 資料給視圖使用
     * 
     * @param string $moduleKey 模組鍵值
     * @return array 包含 metaOverride 的陣列，可直接 compact 給視圖
     */
    protected function prepareMetaData(string $moduleKey): array
    {
        return [
            'metaOverride' => $this->getModuleMeta($moduleKey)
        ];
    }
}