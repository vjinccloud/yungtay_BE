<?php
namespace App\Services;

use App\Repositories\BannerRepository;

class BannerService extends BaseService
{
    // 啟用刪除後自動重整排序
    protected $autoNormalizeAfterDelete = true;
    protected $sortColumn = 'sort_order';
    
    public function __construct(
        private BannerRepository $banner
    ) {
        parent::__construct($banner);
    }

    /**
     * 覆寫此方法，表示這個模型有圖片
     */
    protected function hasImages($model)
    {
        return true;
    }

    /**
     * 自訂圖片刪除邏輯
     * Banner 有兩個圖片：桌機版和手機版
     */
    protected function deleteRelatedImages($model)
    {
        // 刪除桌機版圖片
        if ($model->desktopImage) {
            $this->imageRepository->deleteImgFile($model->desktopImage);
            $this->imageRepository->deleteImg($model->desktopImage);
        }
        
        // 刪除手機版圖片
        if ($model->mobileImage) {
            $this->imageRepository->deleteImgFile($model->mobileImage);
            $this->imageRepository->deleteImg($model->mobileImage);
        }
    }

    /**
     * 設定狀態變更事件標題
     */
    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('title', 'zh_TW') ?? '';
        $model->event_status_title = "首頁輪播{$action}-{$title}";
    }
    
    /**
     * 覆寫模組標題，用於排序事件
     */
    protected function getModuleTitle()
    {
        return '首頁輪播';
    }

    /**
     * 覆寫排序事件標題
     */
    protected function getSortEventTitle($model)
    {
        return '首頁輪播';
    }

    /**
     * 取得格式化的資料（給編輯表單用）
     */
    public function getFormData($id)
    {
        return $this->banner->getDetail($id);
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
            $result['redirect'] = route('admin.banners');
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
     * 覆寫 updateStatus 方法，狀態變更後清除快取
     */
    public function updateStatus($id)
    {
        $result = parent::updateStatus($id);
        
        // 清除首頁快取
        $this->clearHomePageCache();
        
        return $result;
    }
    

    /**
     * 取得前台啟用的輪播圖
     */
    public function getActiveBanners()
    {
        return $this->banner->getActiveBanners();
    }

    /**
     * 更新排序（如果需要的話）
     * 繼承自 BaseService 的 updateSort 方法即可使用
     */
}