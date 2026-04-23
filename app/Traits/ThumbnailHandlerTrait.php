<?php
// app/Traits/ThumbnailHandlerTrait.php

namespace App\Traits;

use App\Jobs\GenerateVideoThumbnail;
use App\Services\Thumbnail\VideoThumbnailManager;
use Illuminate\Support\Facades\Log;

trait ThumbnailHandlerTrait
{
    /**
     * 為影片集數生成縮圖（供 EpisodeServiceTrait 呼叫）
     * 
     * @param mixed $episode 影片集數實體
     * @return void
     */
    protected function generateThumbnailForEpisode($episode): void
    {
        if (!$episode || !$episode->id) {
            return;
        }
        
        Log::info('ThumbnailHandler: 準備生成縮圖', [
            'episode_id' => $episode->id,
            'video_type' => $episode->video_type
        ]);
        
        // 派發縮圖生成 Job
        $this->dispatchThumbnailJob($episode->id);
    }
    
    /**
     * 處理縮圖生成邏輯
     * 
     * @param mixed $entity 實體物件（Episode, Program 等）
     * @param array $attributes 提交的資料
     * @param int|null $id 編輯時的 ID（null 表示新增）
     * @return void
     */
    protected function handleThumbnailGeneration($entity, array $attributes, ?int $id = null): void
    {
        if (!$entity || !$entity->id) {
            return;
        }

        $needNewThumbnail = false;
        
        if (!$id) {
            // 新增，需要生成縮圖
            $needNewThumbnail = true;
            Log::info('ThumbnailHandler: 新增實體，準備生成縮圖', [
                'entity_id' => $entity->id,
                'entity_type' => get_class($entity)
            ]);
        } else {
            // 編輯，檢查是否需要重新生成
            $needNewThumbnail = $this->checkIfThumbnailUpdateNeeded($entity, $attributes, $id);
        }
        
        // 派發縮圖生成 Job
        if ($needNewThumbnail) {
            // 編輯時強制重新生成（因為已經檢測到變更）
            $force = ($id !== null); // 編輯時設為 true
            $this->dispatchThumbnailJob($entity->id, $force);
        }
    }

    /**
     * 檢查是否需要更新縮圖
     * 
     * @param mixed $entity 更新後的實體
     * @param array $attributes 提交的資料
     * @param int $originalId 原始 ID
     * @return bool
     */
    protected function checkIfThumbnailUpdateNeeded($entity, array $attributes, int $originalId): bool
    {
        // 取得原始資料（需要子類別實作 getOriginalEntity 方法）
        $originalEntity = $this->getOriginalEntity($originalId);
        
        if (!$originalEntity) {
            return false;
        }

        // 注意：不要在這裡先刪除舊的縮圖
        // VideoThumbnailManager::generateThumbnail 會在事務中處理刪除和生成
        // 這樣可以確保如果生成失敗，舊的縮圖還在
        
        // 檢查是否需要生成新縮圖
        $needNewThumbnail = false;
        
        // 檢查影片類型是否改變
        if (isset($attributes['video_type']) && $attributes['video_type'] !== $originalEntity->video_type) {
            Log::info('ThumbnailHandler: 影片類型改變，需要重新生成縮圖', [
                'entity_id' => $entity->id,
                'old_type' => $originalEntity->video_type,
                'new_type' => $attributes['video_type']
            ]);
            $needNewThumbnail = true;
        }
        // YouTube URL 改變
        elseif ($attributes['video_type'] === 'youtube' && 
            isset($attributes['youtube_url']) && 
            $attributes['youtube_url'] !== $originalEntity->youtube_url) {
            Log::info('ThumbnailHandler: YouTube URL 改變，需要重新生成縮圖', [
                'entity_id' => $entity->id,
                'old_url' => $originalEntity->youtube_url,
                'new_url' => $attributes['youtube_url']
            ]);
            $needNewThumbnail = true;
        }
        // 上傳新檔案或檔案路徑改變
        elseif ($attributes['video_type'] === 'upload') {
            // 檢查檔案路徑是否改變
            $oldPath = $originalEntity->video_file_path ?? '';
            $newPath = $attributes['video_file_path'] ?? '';
            
            if ($oldPath !== $newPath) {
                Log::info('ThumbnailHandler: 影片檔案改變，需要重新生成縮圖', [
                    'entity_id' => $entity->id,
                    'old_path' => $oldPath,
                    'new_path' => $newPath
                ]);
                $needNewThumbnail = true;
            }
        }
        // 其他情況：未改變影片來源，保留原有縮圖（避免不必要的刪除風險）
        else {
            Log::info('ThumbnailHandler: 編輯影片但未改變影片來源，保留原有縮圖', [
                'entity_id' => $entity->id
            ]);
            $needNewThumbnail = false;
        }

        return $needNewThumbnail;
    }

    /**
     * 派發縮圖生成 Job
     * 
     * @param int $entityId
     * @param bool $force 是否強制重新生成
     * @return void
     */
    protected function dispatchThumbnailJob(int $entityId, bool $force = false): void
    {
        // 判斷是影音還是節目
        $episodeType = 'drama';
        if (method_exists($this, 'getEpisodeType')) {
            $episodeType = $this->getEpisodeType();
        }
        
        // 縮圖生成使用同步執行（立即執行），避免前端沒有圖片
        // 根據配置決定是否使用同步執行
        $useSyncForThumbnails = config('ffmpeg.sync_generation', true);
        
        if ($useSyncForThumbnails) {
            // 同步執行（立即生成縮圖）
            try {
                Log::info('ThumbnailHandler: 開始同步生成縮圖', [
                    'entity_id' => $entityId,
                    'episode_type' => $episodeType,
                    'force' => $force
                ]);
                
                // 使用 dispatchSync 立即執行
                GenerateVideoThumbnail::dispatchSync($entityId, $force, $episodeType);
                
                Log::info('ThumbnailHandler: 縮圖同步生成完成', [
                    'entity_id' => $entityId,
                    'episode_type' => $episodeType
                ]);
            } catch (\Exception $e) {
                Log::error('ThumbnailHandler: 同步生成縮圖失敗', [
                    'entity_id' => $entityId,
                    'episode_type' => $episodeType,
                    'error' => $e->getMessage()
                ]);
                // 失敗時改用隊列重試
                GenerateVideoThumbnail::dispatch($entityId, $force, $episodeType)
                    ->onQueue(config('ffmpeg.queue.queue_name', 'thumbnails'));
            }
        } else {
            // 使用隊列（非同步執行）
            GenerateVideoThumbnail::dispatch($entityId, $force, $episodeType)
                ->onQueue(config('ffmpeg.queue.queue_name', 'thumbnails'))
                ->delay(now()->addSeconds(config('ffmpeg.queue.process_delay', 5)));
                
            Log::info('ThumbnailHandler: 已派發縮圖生成 Job 到隊列', [
                'entity_id' => $entityId,
                'episode_type' => $episodeType,
                'force' => $force
            ]);
        }
    }

    /**
     * 刪除縮圖（如果存在）
     * 
     * @param mixed $entity
     * @return void
     */
    protected function deleteThumbnailIfExists($entity): void
    {
        try {
            $thumbnailManager = app(VideoThumbnailManager::class);
            $thumbnailManager->deleteThumbnail($entity);
            
            Log::info('ThumbnailHandler: 已刪除舊縮圖', [
                'entity_id' => $entity->id
            ]);
        } catch (\Exception $e) {
            Log::warning('ThumbnailHandler: 刪除縮圖失敗', [
                'entity_id' => $entity->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 刪除影片的縮圖（供 EpisodeServiceTrait 呼叫）
     * 
     * @param mixed $episode 影片集數實體
     * @return void
     */
    protected function deleteThumbnail($episode): void
    {
        $this->deleteThumbnailIfExists($episode);
    }
    
    /**
     * 處理實體刪除時的縮圖清理
     * 
     * @param mixed $entity
     * @return void
     */
    protected function handleThumbnailDeletion($entity): void
    {
        if (!$entity) {
            return;
        }

        $this->deleteThumbnailIfExists($entity);
    }

    /**
     * 取得原始實體（需要子類別實作）
     * 
     * @param int $id
     * @return mixed
     */
    abstract protected function getOriginalEntity(int $id);
}