<?php
namespace App\Services;

use App\Repositories\NewsRepository;

class NewsService extends BaseService
{
    public function __construct(
        private NewsRepository $news,
    ) {
        parent::__construct($news);
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
        $model->event_status_title = "最新消息{$action}-{$title}";
    }

    /**
     * 取得格式化的資料（給編輯表單用）
     */
    public function getFormData($id)
    {
        return $this->news->getDetail($id);
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '最新消息 - 信吉衛視';
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

    /**
     * 切換首頁曝光文章狀態
     * 最多只能有4則
     */
    public function toggleHomepageFeatured($id)
    {
        try {
            $news = $this->news->find($id);
            
            // 如果要開啟，檢查是否已達上限
            if (!$news->is_homepage_featured) {
                $currentCount = $this->news->countHomepageFeatured();
                if ($currentCount >= 4) {
                    return [
                        'status' => false,
                        'msg' => '首頁曝光文章已達上限（最多4則），請先取消其他文章的首頁曝光設定',
                    ];
                }
            }
            
            $news->is_homepage_featured = !$news->is_homepage_featured;
            $news->save();
            
            return [
                'status' => true,
                'msg' => $news->is_homepage_featured ? '已設為首頁曝光文章' : '已取消首頁曝光',
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'msg' => '操作失敗：' . $e->getMessage(),
            ];
        }
    }

    /**
     * 切換置頂文章狀態
     * 最多只能有3則
     */
    public function togglePinned($id)
    {
        try {
            $news = $this->news->find($id);
            
            // 如果要開啟，檢查是否已達上限
            if (!$news->is_pinned) {
                $currentCount = $this->news->countPinned();
                if ($currentCount >= 3) {
                    return [
                        'status' => false,
                        'msg' => '置頂文章已達上限（最多3則），請先取消其他文章的置頂設定',
                    ];
                }
            }
            
            $news->is_pinned = !$news->is_pinned;
            $news->save();
            
            return [
                'status' => true,
                'msg' => $news->is_pinned ? '已設為置頂文章' : '已取消置頂',
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'msg' => '操作失敗：' . $e->getMessage(),
            ];
        }
    }

}
