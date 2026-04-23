<?php

namespace App\Services;

use App\Repositories\UserCollectionRepository;
use App\Models\UserCollection;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\Log;

class CollectionService
{
    use CommonTrait;

    protected $collectionRepository;

    public function __construct(UserCollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * 檢查用戶是否收藏了特定內容
     */
    public function isCollected($userId, $contentType, $contentId)
    {
        try {
            return $this->collectionRepository->isCollected($userId, $contentType, $contentId);
        } catch (\Exception $e) {
            Log::error('檢查收藏狀態失敗', [
                'user_id' => $userId,
                'content_type' => $contentType,
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 新增收藏
     */
    public function addCollection($data)
    {
        try {
            $result = $this->collectionRepository->addCollection($data);
            
            if ($result === false) {
                return $this->ReturnHandle(false, __('frontend.btn.already_collected'));
            }

            return $this->ReturnHandle(true, __('frontend.btn.collection_success'));

        } catch (\Exception $e) {
            Log::error('新增收藏失敗', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return $this->ReturnHandle(false, __('frontend.btn.collection_failed'));
        }
    }

    /**
     * 移除收藏
     */
    public function removeCollection($data)
    {
        try {
            $result = $this->collectionRepository->removeCollection($data['user_id'], $data['content_type'], $data['content_id']);
            
            if ($result->isEmpty()) {
                return $this->ReturnHandle(false, '找不到收藏記錄');
            }

            return $this->ReturnHandle(true, __('frontend.btn.collection_removed'));

        } catch (\Exception $e) {
            Log::error('移除收藏失敗', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return $this->ReturnHandle(false, __('frontend.btn.collection_failed'));
        }
    }


    /**
     * 批次檢查收藏狀態
     */
    public function batchCheckCollected($userId, $contentType, $contentIds)
    {
        try {
            $result = $this->collectionRepository->batchCheckCollected($userId, $contentType, $contentIds);
            
            return $this->ReturnHandle(true, '批次檢查完成', null, $result);

        } catch (\Exception $e) {
            Log::error('批次檢查收藏狀態失敗', [
                'user_id' => $userId,
                'content_type' => $contentType,
                'content_ids' => $contentIds,
                'error' => $e->getMessage()
            ]);
            return $this->ReturnHandle(false, '批次檢查失敗');
        }
    }

    /**
     * 取得用戶收藏列表（全部，不分頁）
     */
    public function getUserAllCollections($userId, $contentType = null)
    {
        try {
            $collections = $this->collectionRepository->getUserAllCollections($userId, $contentType);
            
            return $this->ReturnHandle(true, '取得收藏列表成功', null, $collections);

        } catch (\Exception $e) {
            Log::error('取得收藏列表失敗', [
                'user_id' => $userId,
                'content_type' => $contentType,
                'error' => $e->getMessage()
            ]);
            return $this->ReturnHandle(false, '取得收藏列表失敗');
        }
    }

    /**
     * 取得用戶各類型收藏數量
     */
    public function getUserCollectionCounts($userId)
    {
        try {
            $counts = $this->collectionRepository->getUserCollectionCounts($userId);
            
            return $this->ReturnHandle(true, '取得收藏統計成功', null, $counts);

        } catch (\Exception $e) {
            Log::error('取得收藏統計失敗', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return $this->ReturnHandle(false, '取得收藏統計失敗');
        }
    }

}