<?php
namespace App\Services;

use App\Repositories\LiveRepository;

class LiveService extends BaseService
{
    // 啟用刪除後自動重整排序
    protected $autoNormalizeAfterDelete = true;
    protected $sortColumn = 'sort_order';
    
    public function __construct(
        private LiveRepository $liveRepository,
    ) {
        parent::__construct($liveRepository);
    }

    // 覆寫此方法，表示這個模型有縮圖
    protected function hasImages($model)
    {
        return true;
    }

    // 可選：自訂圖片刪除邏輯（包含縮圖）
    protected function deleteRelatedImages($model)
    {
        // 刪除 YouTube 縮圖
        $this->deleteImagesByType($model, ['video_thumbnail']);
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('title', 'zh_TW') ?? '';
        $model->event_status_title = "直播{$action}-{$title}";
    }


    /**
     * 覆寫 save 方法，新增成功後跳轉到列表頁
     */
    public function save(array $attributes, $id = null)
    {
        $result = parent::save($attributes, $id);
        
        // 清除首頁快取
        $this->clearHomePageCache();
        
        // 如果是新增（$id 為 null），設定重定向到列表頁
        if (is_null($id)) {
            $result['redirect'] = route('admin.lives');
        }
        
        return $result;
    }
    
    /**
     * 覆寫 delete 方法，刪除後清除快取
     */
    public function delete($id)
    {
        $result = parent::delete($id);
        
        // 清除首頁快取
        $this->clearHomePageCache();
        
        return $result;
    }
    
    /**
     * 覆寫 checkedStatus 方法，狀態變更後清除快取
     */
    public function checkedStatus($id, $statusField = null)
    {
        $result = parent::checkedStatus($id, $statusField);
        
        // 清除首頁快取
        $this->clearHomePageCache();
        
        return $result;
    }

    /**
     * 前台取得頁面資料
     * 
     * @param int|null $id 指定的直播 ID
     * @return array
     */
    public function getPageData($id = null)
    {
        // 取得所有啟用的直播
        $lives = $this->liveRepository->getActiveLives();
        
        // 如果沒有任何直播，返回空資料
        if ($lives->isEmpty()) {
            return [
                'lives' => [],
                'currentLive' => null,
                'sidebarLives' => [],
                'currentId' => null
            ];
        }
        
        // 決定當前播放的直播
        $currentId = $id;
        
        // 如果有指定 ID，檢查是否存在
        if ($id) {
            $currentLive = $lives->firstWhere('id', $id);
            if (!$currentLive) {
                // ID 不存在，使用第一個
                $currentLive = $lives->first();
                $currentId = $currentLive['id'];
            }
        } else {
            // 沒有指定 ID，使用第一個
            $currentLive = $lives->first();
            $currentId = $currentLive['id'];
        }
        
        // 處理 YouTube URL，提取影片 ID
        $videoId = null;
        if (!empty($currentLive['youtube_url'])) {
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', 
                      $currentLive['youtube_url'], $matches);
            $videoId = $matches[1] ?? null;
        }
        $currentLive['video_id'] = $videoId;
        
        // 準備側邊欄列表（排除當前播放的）
        $sidebarLives = $lives->reject(function ($live) use ($currentId) {
            return $live['id'] == $currentId;
        })->values();
        
        return [
            'lives' => $lives,
            'currentLive' => $currentLive,
            'sidebarLives' => $sidebarLives,
            'currentId' => $currentId
        ];
    }

    /**
     * 取得編輯資料
     */
    public function getEditData($id)
    {
        return $this->liveRepository->getEditData($id);
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '直播 - 信吉衛視';
    }

    /**
     * 覆寫排序事件標題（只返回模組名稱，不含品牌）
     */
    protected function getSortEventTitle($model)
    {
        return '直播';
    }
}