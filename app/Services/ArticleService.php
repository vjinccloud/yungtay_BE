<?php

namespace App\Services;

use App\Repositories\ArticleRepository;

class ArticleService extends BaseService
{
    public function __construct(
        private ArticleRepository $article,
    ) {
        parent::__construct($article);
    }

    // 覆寫此方法，表示這個模型有圖片
    protected function hasImages($model)
    {
        return true;
    }

    // 自訂圖片刪除邏輯
    protected function deleteRelatedImages($model)
    {
        // 因為是 morphOne 關聯，直接取得並刪除單一圖片
        if ($model->image) {
            $this->imageRepository->deleteImgFile($model->image);
            $this->imageRepository->deleteImg($model->image);
        }
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('title', 'zh_TW') ?? '';
        $model->event_status_title = "新聞{$action}-{$title}";
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
            $result['redirect'] = route('admin.articles');
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
     * 取得格式化的資料（給編輯表單用）
     */
    public function getFormData($id)
    {
        return $this->article->getDetail($id);
    }

    /**
     * 取得分頁資料
     */
    public function paginate($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->article->paginate($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 前台文章相關操作統一入口
     */
    public function getFrontendArticles($limit = 4, $categoryId = null, $excludeId = null)
    {
        return $this->article->getFrontendArticles($limit, $categoryId, $excludeId);
    }

    /**
     * 前台文章列表（分頁）
     */
    public function getFrontendList($perPage = 20, $categoryId = null)
    {
        return $this->article->getFrontendList($perPage, $categoryId);
    }

    /**
     * 前台文章詳情
     */
    public function getFrontendDetail($id)
    {
        return $this->article->getFrontendDetail($id);
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '新聞 - 信吉衛視';
    }

     /**
     * 取得詳情頁 SEO
     */
    public function getDetailSEO($data)
    {
        return [
            'title' => $data['title'].' - '.$this->getModuleTitle(),
            'description' => strip_tags($data['content'] ??  ''),
            'og_image' => isset($data['image']) ? $this->resolveImageUrl($data['image']) : null,
        ];
    }


}