<?php
// app/Services/Thumbnail/VideoThumbnailManager.php

namespace App\Services\Thumbnail;

use App\Models\DramaEpisode;
use App\Models\ProgramEpisode;
use App\Models\ImageManagement;
use App\Services\Thumbnail\Contracts\ThumbnailStrategyInterface;
use App\Services\Thumbnail\Strategies\YouTubeThumbnailStrategy;
use App\Services\Thumbnail\Strategies\UploadedVideoThumbnailStrategy;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VideoThumbnailManager
{
    /**
     * @var array<ThumbnailStrategyInterface>
     */
    protected array $strategies = [];

    public function __construct()
    {
        // 註冊策略
        $this->registerStrategy(new YouTubeThumbnailStrategy());
        $this->registerStrategy(new UploadedVideoThumbnailStrategy());
    }

    /**
     * 註冊縮圖生成策略
     */
    public function registerStrategy(ThumbnailStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    /**
     * 生成影片縮圖
     * @param DramaEpisode|ProgramEpisode $episode
     */
    public function generateThumbnail($episode): bool
    {
        try {
            // 在事務中處理
            return DB::transaction(function () use ($episode) {
                // 保存舊縮圖的參考（用於後續刪除）
                $oldThumbnail = $episode->thumbnail;

                // 找到適合的策略
                $strategy = $this->findStrategy($episode);

                if (!$strategy) {
                    Log::warning('VideoThumbnailManager: 找不到適合的縮圖生成策略', [
                        'episode_id' => $episode->id,
                        'video_type' => $episode->video_type
                    ]);
                    return false;
                }

                Log::info('VideoThumbnailManager: 使用策略生成縮圖', [
                    'episode_id' => $episode->id,
                    'strategy' => $strategy->getName()
                ]);

                // 先生成新縮圖
                $thumbnailPath = $strategy->generate($episode);

                if (!$thumbnailPath) {
                    Log::warning('VideoThumbnailManager: 新縮圖生成失敗，保留舊縮圖', [
                        'episode_id' => $episode->id
                    ]);
                    return false;
                }

                // 儲存新縮圖到資料庫
                $saved = $this->saveThumbnailRecord($episode, $thumbnailPath);

                if (!$saved) {
                    Log::warning('VideoThumbnailManager: 新縮圖儲存失敗，保留舊縮圖', [
                        'episode_id' => $episode->id
                    ]);
                    // 清理剛生成的檔案
                    $disk = Storage::disk(config('ffmpeg.thumbnail.disk', 'public'));
                    if ($disk->exists($thumbnailPath)) {
                        $disk->delete($thumbnailPath);
                    }
                    return false;
                }

                // 新縮圖成功後，才刪除舊縮圖
                if ($oldThumbnail) {
                    $this->deleteOldThumbnail($oldThumbnail);
                }

                Log::info('VideoThumbnailManager: 縮圖更新成功', [
                    'episode_id' => $episode->id,
                    'new_path' => $thumbnailPath
                ]);

                return true;
            });

        } catch (\Exception $e) {
            Log::error('VideoThumbnailManager: 生成縮圖失敗', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 刪除影片縮圖
     * @param DramaEpisode|ProgramEpisode $episode
     */
    public function deleteThumbnail($episode): bool
    {
        try {
            $thumbnail = $episode->thumbnail;
            
            if (!$thumbnail) {
                return true;
            }

            // 使用 Storage Facade 刪除檔案
            $disk = Storage::disk(config('ffmpeg.thumbnail.disk', 'public'));
            
            if ($disk->exists($thumbnail->path)) {
                $disk->delete($thumbnail->path);
            }

            // 刪除資料庫記錄
            $thumbnail->delete();
            
            Log::info('VideoThumbnailManager: 縮圖刪除成功', [
                'episode_id' => $episode->id,
                'path' => $thumbnail->path
            ]);
            
            return true;

        } catch (\Exception $e) {
            Log::error('VideoThumbnailManager: 刪除縮圖失敗', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 刪除舊的縮圖（傳入 ImageManagement 物件）
     * @param ImageManagement $thumbnail
     */
    protected function deleteOldThumbnail($thumbnail): bool
    {
        try {
            // 使用 Storage Facade 刪除檔案
            $disk = Storage::disk(config('ffmpeg.thumbnail.disk', 'public'));

            if ($disk->exists($thumbnail->path)) {
                $disk->delete($thumbnail->path);
            }

            // 刪除資料庫記錄
            $thumbnail->delete();

            Log::info('VideoThumbnailManager: 舊縮圖刪除成功', [
                'thumbnail_id' => $thumbnail->id,
                'path' => $thumbnail->path
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('VideoThumbnailManager: 刪除舊縮圖失敗', [
                'thumbnail_id' => $thumbnail->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 取得縮圖 URL
     * @param DramaEpisode|ProgramEpisode $episode
     */
    public function getThumbnailUrl($episode): ?string
    {
        $thumbnail = $episode->thumbnail;
        
        if (!$thumbnail) {
            return $this->getDefaultThumbnail();
        }

        // 使用 Storage URL
        $disk = Storage::disk(config('ffmpeg.thumbnail.disk', 'public'));
        
        if ($disk->exists($thumbnail->path)) {
            return $disk->url($thumbnail->path);
        }

        return $this->getDefaultThumbnail();
    }

    /**
     * 批次生成縮圖
     * @param array $episodeIds
     * @param bool $force
     * @param string $episodeType 'drama' or 'program'
     */
    public function batchGenerate(array $episodeIds, bool $force = false, string $episodeType = 'drama'): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($episodeIds as $episodeId) {
            try {
                // 支援影音和節目兩種類型
                $episode = $episodeType === 'program' 
                    ? ProgramEpisode::find($episodeId)
                    : DramaEpisode::find($episodeId);
                
                if (!$episode) {
                    $results['failed']++;
                    $results['errors'][] = "Episode {$episodeId} not found";
                    continue;
                }

                // 如果已有縮圖且不強制重新生成
                if (!$force && $episode->thumbnail) {
                    $results['skipped']++;
                    continue;
                }

                if ($this->generateThumbnail($episode)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to generate thumbnail for episode {$episodeId}";
                }
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Episode {$episodeId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * 找到適合的策略
     * @param DramaEpisode|ProgramEpisode $episode
     */
    protected function findStrategy($episode): ?ThumbnailStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($episode)) {
                return $strategy;
            }
        }
        
        return null;
    }

    /**
     * 儲存縮圖記錄到資料庫
     * @param DramaEpisode|ProgramEpisode $episode
     */
    protected function saveThumbnailRecord($episode, string $path): bool
    {
        try {
            $filename = basename($path);
            
            ImageManagement::create([
                'attachable_type' => get_class($episode),
                'attachable_id' => $episode->id,
                'image_type' => 'video_thumbnail',
                'path' => $path,
                'filename' => $filename,
                'ext' => 'jpg',
                'title' => "第{$episode->seq}集縮圖",
                'seq' => 1
            ]);

            Log::info('VideoThumbnailManager: 縮圖記錄儲存成功', [
                'episode_id' => $episode->id,
                'path' => $path
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('VideoThumbnailManager: 儲存縮圖記錄失敗', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 取得預設縮圖
     */
    protected function getDefaultThumbnail(): ?string
    {
        return config('ffmpeg.fallback.default_thumbnail');
    }
}