<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * 主題 Service 共用邏輯
 * 用於 DramaThemeService 和 ProgramThemeService
 * 處理主題管理、排序、快取等共用功能
 */
trait ThemeServiceTrait
{
    /**
     * 初始化主題排序設定
     * 在 Service 的 constructor 中呼叫
     */
    protected function initializeThemeSorting(): void
    {
        $this->autoNormalizeAfterDelete = true;
        $this->sortColumn = 'sort_order';
    }
    
    /**
     * 取得內容類型（drama 或 program）
     * @return string
     */
    abstract protected function getContentType(): string;
    
    /**
     * 取得內容的中文名稱（影音主題 或 節目主題）
     * @return string
     */
    abstract protected function getThemeTypeChinese(): string;
    
    /**
     * 取得主題 Repository
     * @return mixed
     */
    abstract protected function getThemeRepository();
    
    /**
     * 取得關聯 Repository
     * @return mixed
     */
    abstract protected function getRelationRepository();
    
    /**
     * 取得編輯路由名稱
     * @return string
     */
    abstract protected function getEditRouteName(): string;

    /**
     * 儲存主題資料（新增或更新）
     *
     * @param array $data 主題資料
     * @param int|null $id 主題 ID（更新時使用）
     * @return array
     */
    public function save(array $data, $id = null)
    {
        try {
            DB::beginTransaction();

            // Repository 會自動處理 sort_order 和關聯，回傳 id
            $theme = $this->repository->save($data, $id);

            DB::commit();

            // 觸發對應的事件
            if (is_null($id)) {
                // 新增主題
                $this->eventService->fireDataCreated($theme);
                $message = $this->getThemeTypeChinese() . '新增成功';
            } else {
                // 編輯主題
                $this->eventService->fireDataUpdated($theme);
                $message = $this->getThemeTypeChinese() . '更新成功';
            }

            // 清除前台快取
            $this->clearFrontendCache();

            $result = $this->ReturnHandle(true, $message);
            
            // 只有新增時才跳轉到編輯頁，編輯時不跳轉
            if (is_null($id)) {
                $result['redirect'] = route($this->getEditRouteName(), $theme->id);
            }
            
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 設定狀態變更的事件標題
     */
    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('name', 'zh_TW') ?? '';
        $model->event_status_title = $this->getThemeTypeChinese() . "{$action}-{$title}";
    }

    /**
     * 根據 ID 查詢主題名稱
     *
     * @param int $id
     * @return array|null
     */
    public function findName($id)
    {
        return $this->getThemeRepository()->findName($id);
    }

    /**
     * 取得主題下的內容列表
     *
     * @param int $themeId
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function getThemeContents($themeId, int $perPage = 10, int $page = 1)
    {
        $id = (int) $themeId;
        $contentType = $this->getContentType();
        $methodName = 'getTheme' . ucfirst($contentType) . 's';
        
        return $this->getRelationRepository()->$methodName($id, $perPage, $page);
    }

    /**
     * 更新主題內容排序
     *
     * @param array $data 包含 themeId 和 content_ids 的陣列
     * @return array
     */
    public function updateRelationSortOrder(array $data): array
    {
        try {
            // 檢查主題是否存在
            $theme = $this->getThemeRepository()->find($data['themeId']);
            if (!$theme) {
                throw new \Exception('主題不存在');
            }

            $contentType = $this->getContentType();
            $contentIds = $data[$contentType . '_ids'] ?? [];

            if (empty($contentIds)) {
                throw new \Exception('排序資料不能為空');
            }

            // 執行排序更新
            $this->getRelationRepository()->updateSortOrder($data['themeId'], $contentIds);

            // 清除前台快取，讓前台立即反映排序變更
            $this->clearFrontendCache();

            $result = $this->ReturnHandle(true, '排序更新成功');

        } catch (\Exception $e) {
            $result = $this->ReturnHandle(false, $e->getMessage());
        }
        return $result;
    }

    /**
     * 從主題中移除內容
     *
     * @param int $relationId 關聯 ID
     * @return array
     */
    public function removeThemeContent(int $relationId): array
    {
        $contentName = $this->getContentType() === 'drama' ? '影音' : '節目';

        try {
            // 執行刪除
            $deleted = $this->getRelationRepository()->deleteRelation($relationId);

            if ($deleted) {
                // 清除前台快取，讓前台立即反映變更
                $this->clearFrontendCache();

                return $this->ReturnHandle(true, $contentName . '移除成功');
            }

            return $this->ReturnHandle(false, $contentName . '移除失敗');

        } catch (\Exception $e) {
            \Log::error('從主題移除' . $contentName . '失敗', [
                'relation_id' => $relationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return $this->getThemeTypeChinese(); // 影音主題 或 節目主題
    }
    
    /**
     * 更新主題排序 - 使用 BaseService 的 updateSort 並清除快取
     *
     * @param array $payload 主題 ID 陣列或物件陣列
     * @return array
     */
    public function sort($payload)
    {
        // 格式轉換：支援純 ID 陣列或物件陣列
        $sortData = [];
        if (is_array($payload) && !empty($payload)) {
            if (is_array($payload[0]) && array_key_exists('id', $payload[0])) {
                // 已是物件格式 [{id:1},{id:2}]
                $sortData = $payload;
            } else {
                // 純 ID 陣列 [1,2,3]，轉換為物件格式
                $sortData = array_map(fn($id) => ['id' => $id], $payload);
            }
        }
        
        // 使用 BaseService 的 updateSort（已包含事件處理）
        $result = parent::updateSort($sortData, 'sort_order');
        
        // 如果成功，清除前端快取
        if ($result['status']) {
            $this->clearFrontendCache();
        }
        
        return $result;
    }

    /**
     * 刪除主題（包含排序調整）
     *
     * @param int $id
     * @return array
     */
    public function delete($id): array
    {
        // 取得主題名稱（用於自訂訊息）
        $theme = $this->getThemeRepository()->find($id);
        if (!$theme) {
            return $this->ReturnHandle(false, $this->getThemeTypeChinese() . '不存在');
        }
        $themeName = $theme->getTranslation('name', 'zh_TW');
        
        // 執行父類別的 delete（包含自動重整排序和事件觸發）
        $result = parent::delete($id);
        
        // 如果刪除成功，清除前台快取並自訂訊息
        if ($result['status']) {
            $this->clearFrontendCache();
            $result['message'] = $this->getThemeTypeChinese() . "「{$themeName}」已成功刪除";
        }
        
        return $result;
    }

    /**
     * 取得前台主題列表（包含內容）
     *
     * @param int|null $contentsPerTheme 每個主題顯示的內容數量，null 表示不限制
     * @return \Illuminate\Support\Collection
     */
    public function getFrontendThemesWithContents($contentsPerTheme = null)
    {
        $contentType = $this->getContentType();
        
        try {
            $cacheKey = "frontend_{$contentType}_themes_with_{$contentType}s_{$contentsPerTheme}_" . app()->getLocale();
            $cacheTTL = 60 * 10; // 快取 10 分鐘

            return Cache::remember($cacheKey, $cacheTTL, function() use ($contentsPerTheme, $contentType) {
                $methodName = 'getActiveThemesWith' . ucfirst($contentType) . 's';
                return $this->getThemeRepository()->$methodName($contentsPerTheme);
            });
        } catch (\Exception $e) {
            \Log::error('取得前台' . $this->getThemeTypeChinese() . '失敗', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * 取得前台篩選用的主題列表
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFrontendThemesForFilter()
    {
        $contentType = $this->getContentType();
        
        try {
            $cacheKey = "frontend_{$contentType}_themes_filter_" . app()->getLocale();
            $cacheTTL = 60 * 30; // 快取 30 分鐘（篩選選項變動較少）

            return Cache::remember($cacheKey, $cacheTTL, function() {
                return $this->getThemeRepository()->getActiveThemesForFilter();
            });
        } catch (\Exception $e) {
            \Log::error('取得前台篩選主題失敗', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * 清除前台相關快取
     * 當主題或內容有更新時呼叫
     */
    public function clearFrontendCache()
    {
        $contentType = $this->getContentType();
        $locales = ['zh_TW', 'en'];
        $contentsPerThemeOptions = [null, 5, 8, 10]; // 包含 null（不限制數量）

        foreach ($locales as $locale) {
            // 清除篩選快取
            Cache::forget("frontend_{$contentType}_themes_filter_{$locale}");

            // 清除不同顯示數量的主題快取
            foreach ($contentsPerThemeOptions as $count) {
                Cache::forget("frontend_{$contentType}_themes_with_{$contentType}s_{$count}_{$locale}");
            }
        }
    }
}