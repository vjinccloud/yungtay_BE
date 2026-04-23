<?php

namespace App\Repositories;

use App\Models\UserCollection;

class UserCollectionRepository extends BaseRepository
{
    public function __construct(UserCollection $model)
    {
        parent::__construct($model);
    }

    /**
     * 檢查用戶是否收藏了特定內容
     */
    public function isCollected($userId, $contentType, $contentId)
    {
        return $this->findWhere([
            'user_id' => $userId,
            'content_type' => $contentType,
            'content_id' => $contentId
        ]) !== null;
    }

    /**
     * 新增收藏
     */
    public function addCollection($data)
    {
        // 先檢查是否已存在
        if ($this->isCollected($data['user_id'], $data['content_type'], $data['content_id'])) {
            return false; // 已收藏
        }
        
        // 使用 BaseRepository 的 save 方法
        return parent::save($data);
    }

    /**
     * 移除收藏
     */
    public function removeCollection($userId, $contentType, $contentId)
    {
        return $this->getWhere([
            'user_id' => $userId,
            'content_type' => $contentType,
            'content_id' => $contentId
        ])->each(function ($collection) {
            $collection->delete();
        });
    }

    /**
     * 批次檢查收藏狀態
     */
    public function batchCheckCollected($userId, $contentType, $contentIds)
    {
        if (empty($contentIds)) {
            return [];
        }

        $collections = $this->query(function ($query) use ($userId, $contentType, $contentIds) {
            return $query->where('user_id', $userId)
                ->where('content_type', $contentType)
                ->whereIn('content_id', $contentIds);
        })->pluck('content_id')->toArray();

        // 回傳格式：[content_id => is_collected]
        $result = [];
        foreach ($contentIds as $id) {
            $result[$id] = in_array($id, $collections);
        }

        return $result;
    }

    /**
     * 取得用戶收藏列表（全部，不分頁）
     */
    public function getUserAllCollections($userId, $contentType = null)
    {
        $query = $this->model->with(['article', 'drama', 'program', 'radio'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');
        
        if ($contentType) {
            $query->where('content_type', $contentType);
        }
        
        return $query->get()->map(function ($collection) {
            return $this->formatCollectionForFrontend($collection);
        })->filter();
    }

    /**
     * 取得用戶各類型收藏數量（不包含直播）
     */
    public function getUserCollectionCounts($userId)
    {
        return $this->model->where('user_id', $userId)
            ->whereNotIn('content_type', ['live']) // 排除直播
            ->selectRaw('content_type, COUNT(*) as count')
            ->groupBy('content_type')
            ->pluck('count', 'content_type')
            ->toArray();
    }

    /**
     * 簡化的收藏資料格式化
     */
    protected function formatCollectionForFrontend($collection)
    {
        $content = $this->getRelatedContent($collection);
        
        if (!$content) {
            return null;
        }
        
        $locale = app()->getLocale();
        
        return [
            'id' => $collection->content_id,
            'title' => $content->getTranslation('title', $locale) ?? '',
            'subtitle' => $this->getSubtitle($content, $collection->content_type, $locale),
            'image' => $this->getImage($content, $collection->content_type),
            'url' => $this->getUrl($content, $collection->content_type),
        ];
    }

    /**
     * 取得相關內容（利用 Model 關聯避免 N+1）
     */
    private function getRelatedContent($collection)
    {
        switch ($collection->content_type) {
            case 'articles':
                return $collection->article;
            case 'drama':
                return $collection->drama;
            case 'program':
                return $collection->program;
            case 'radio':
                return $collection->radio;
            default:
                return null;
        }
    }

    /**
     * 取得副標題
     */
    private function getSubtitle($content, $contentType, $locale)
    {
        switch ($contentType) {
            case 'articles':
                return $content->subtitle ?? '';
            case 'drama':
            case 'program':
                return $content->release_year . ($content->season_number ? ' 第' . $content->season_number . '季' : '');
            case 'radio':
                return $content->getTranslation('media_name', $locale) ?? '';
            default:
                return '';
        }
    }

    /**
     * 取得圖片
     */
    private function getImage($content, $contentType)
    {
        switch ($contentType) {
            case 'articles':
                // 新聞只使用縮圖
                return $content->image_thumbnail ? asset($this->resolveImageUrl($content->image_thumbnail->path)) : asset('frontend/images/default.webp');
            case 'radio':
                // Radio 使用 images 多型關聯，取第一張圖片
                $image = $content->images->first();
                return $image ? asset($this->resolveImageUrl($image->path)) : asset('frontend/images/default.webp');
            case 'drama':
            case 'program':
                // Drama/Program 使用 posterDesktop 關聯方法
                $posterDesktop = $content->posterDesktop;
                return $posterDesktop ? asset($this->resolveImageUrl($posterDesktop->path)) : asset('frontend/images/default.webp');
            default:
                return asset('frontend/images/default.webp');
        }
    }

    /**
     * 取得連結
     */
    private function getUrl($content, $contentType)
    {
        switch ($contentType) {
            case 'articles':
                return route('articles.show', $content->id);
            case 'drama':
                return route('drama.videos.index', $content->id);
            case 'program':
                return route('program.videos.index', $content->id);
            case 'radio':
                return route('radio.show', $content->id);
            default:
                return '#';
        }
    }
}