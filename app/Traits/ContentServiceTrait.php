<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * 內容 Service 共用邏輯
 * 用於 DramaService 和 ProgramService
 * 處理儲存、驗證、編輯資料取得等共用功能
 */
trait ContentServiceTrait
{
    /**
     * 取得內容類型（drama 或 program）
     * @return string
     */
    abstract protected function getContentType(): string;
    
    /**
     * 取得內容的中文名稱（影音 或 節目）
     * @return string
     */
    abstract protected function getContentTypeChinese(): string;
    
    /**
     * 取得影片 Repository
     * @return mixed
     */
    abstract protected function getEpisodeRepository();
    
    /**
     * 取得主要 Repository
     * @return mixed
     */
    abstract protected function getMainRepository();
    
    /**
     * 取得內容列表路由名稱
     * @return string
     */
    abstract protected function getListRouteName(): string;
    
    /**
     * 儲存內容資料（新增或更新）
     *
     * @param array $data 內容資料
     * @param int|null $id 內容 ID（更新時使用）
     * @return array
     */
    public function save(array $data, $id = null)
    {
        try {
            DB::beginTransaction();
            $this->validateHasVideos($id);
            
            // 儲存內容資料
            $content = $this->repository->save($data, $id);
            $this->loadEpisodesForLog($content);
            DB::commit();

            // 觸發對應的事件
            if (is_null($id)) {
                // 新增
                $this->eventService->fireDataCreated($content);
                $message = $this->getContentTypeChinese() . '新增成功';
            } else {
                // 編輯
                $this->eventService->fireDataUpdated($content);
                $message = $this->getContentTypeChinese() . '更新成功';
            }

            // 清除首頁快取
            $this->clearHomePageCache();
            
            // 清除前台內容頁面快取
            $this->clearContentPageCache();
            
            $result = $this->ReturnHandle(true, $message);
            if (is_null($id)) {
                $result['redirect'] = route($this->getListRouteName());
            }
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 驗證是否有影片資料
     *
     * @param int|null $contentId
     * @throws \Exception
     */
    protected function validateHasVideos($contentId = null)
    {
        if (!$this->getEpisodeRepository()->hasVideos($contentId)) {
            throw new \Exception('無影片資料！請切換到「集數管理」標籤新增影片。');
        }
    }

    /**
     * 載入影片關聯並按季別分組（專門用於操作紀錄）
     */
    protected function loadEpisodesForLog($content)
    {
        // 載入影片關聯
        $content->load('episodes');

        // 將影片按季別分組並暫存到 model 屬性中
        $episodesBySeason = $content->episodes->groupBy('season')->map(function ($seasonEpisodes, $season) {
            return [
                'season' => $season,
                'episodes' => $seasonEpisodes->sortBy('seq')->values()->toArray()
            ];
        })->values()->toArray();

        // 暫存分季資料到 model 的動態屬性
        $content->episodes_by_season = $episodesBySeason;
    }

    /**
     * 取得內容列表分頁資料
     */
    public function getDataTableData($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->getMainRepository()->paginate($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 前台篩選內容
     *
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return array
     */
    public function getFilteredContent(array $filters = [], $perPage = 18)
    {
        $contentType = $this->getContentType();
        $methodName = 'getFiltered' . ucfirst($contentType) . 's';
        
        try {
            // 呼叫 Repository 取得篩選結果
            $paginator = $this->getMainRepository()->$methodName($filters, $perPage);
            
            // 將分頁資料轉換為適合前端的格式
            return [
                'success' => true,
                $contentType . 's' => $paginator->items(),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'filters' => $filters
            ];
        } catch (\Exception $e) {
            \Log::error($this->getContentTypeChinese() . '篩選失敗: ' . $e->getMessage());
            
            return [
                'success' => false,
                $contentType . 's' => [],
                'total' => 0,
                'message' => '篩選失敗，請稍後再試'
            ];
        }
    }

    /**
     * 刪除內容
     *
     * @param int $id 內容 ID
     * @return array
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();

            // 取得內容資料
            $content = $this->getMainRepository()->find($id);
            
            if (!$content) {
                return $this->ReturnHandle(false, '找不到該' . $this->getContentTypeChinese());
            }

            // 觸發刪除事件（要在刪除前觸發）
            $this->eventService->fireDataDeleted($content);

            // 刪除內容
            $this->getMainRepository()->delete($id);

            DB::commit();
            
            // 清除首頁快取
            $this->clearHomePageCache();
            
            // 清除前台內容頁面快取
            $this->clearContentPageCache();

            return $this->ReturnHandle(true, $this->getContentTypeChinese() . '刪除成功');

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
        $title = $model->getTranslation('title', 'zh_TW') ?? '';
        $model->event_status_title = $this->getContentTypeChinese() . "{$action}-{$title}";
    }

    /**
     * 取得所有內容選項（供下拉選單使用）
     */
    public function getAllContentOptions()
    {
        return $this->getMainRepository()->getAllForSelect();
    }

    /**
     * 取得編輯表單所需的資料
     *
     * @param int $id 內容 ID
     * @return array
     */
    public function getEditData($id)
    {
        // 取得內容資料
        $contentData = $this->getMainRepository()->getEditFormData($id);

        if (!$contentData) {
            throw new \Exception($this->getContentTypeChinese() . '資料不存在');
        }

        // 取得分類資料
        $categoryService = app(\App\Services\CategoryService::class);
        $contentType = $this->getContentType();
        $categoriesData = $categoryService->getCategoriesForForm(\App\Models\Category::TYPE_DRAMA);
        
        // 根據內容類型調整分類類型
        if ($contentType === 'program') {
            $categoriesData = $categoryService->getCategoriesForForm(\App\Models\Category::TYPE_PROGRAM);
        }

        return [
            $contentType => $contentData,
            'categories' => $categoriesData['main']->toArray(),
            'subcategories' => $categoriesData['sub']->toArray(),
            'videoSeasons' => $contentData['video_seasons'],
        ];
    }

    /**
     * 清除前台內容頁面快取
     */
    private function clearContentPageCache()
    {
        $contentType = $this->getContentType();
        $locales = ['zh_TW', 'en'];
        $contentsPerThemeOptions = [null, 5, 8, 10];

        foreach ($locales as $locale) {
            // 清除篩選快取
            \Cache::forget("frontend_{$contentType}_themes_filter_{$locale}");

            // 清除不同顯示數量的主題快取
            foreach ($contentsPerThemeOptions as $count) {
                \Cache::forget("frontend_{$contentType}_themes_with_{$contentType}s_{$count}_{$locale}");
            }
        }
    }

    /**
     * 取得內容詳情頁 SEO（影音和節目共用）
     */
    public function getDetailSEO($contentData)
    {
        $contentType = $this->getContentType();
        
        // 處理內容資料結構（影音或節目）
        $content = $contentData[$contentType] ?? $contentData;
        
        // 圖片優先順序：當前集數縮圖 > 內容海報
        $og_image = null;
        if (isset($contentData['currentEpisode']['video_embed_url'])) {
            $og_image = $this->resolveImageUrl($contentData['currentEpisode']['video_embed_url']);
        } elseif (isset($content['poster_desktop'])) {
            $og_image = $this->resolveImageUrl($content['poster_desktop']);
        }
        
        return [
            'title' => $content['title'].' - '.$this->getModuleTitle() ?? '',
            'description' => strip_tags($content['description'] ?? ''),
            'og_image' => $og_image,
        ];
    }
}