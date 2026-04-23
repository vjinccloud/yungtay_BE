<?php

namespace App\Jobs;

use App\Models\DramaEpisode;
use App\Models\ProgramEpisode;
use App\Services\Thumbnail\VideoThumbnailManager;
use App\Exceptions\Thumbnail\ThumbnailGenerationException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateVideoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任務最多嘗試次數
     */
    public $tries = 3;

    /**
     * 任務超時時間（秒）
     */
    public $timeout = 300; // 5分鐘

    /**
     * 影片集數 ID
     */
    protected $episodeId;

    /**
     * 是否強制重新生成
     */
    protected $force;

    /**
     * 失敗次數
     */
    protected $failureCount = 0;
    
    /**
     * 影片類型（drama 或 program）
     */
    protected $episodeType;

    /**
     * Create a new job instance.
     */
    public function __construct($episodeId, $force = false, $episodeType = 'drama')
    {
        $this->episodeId = $episodeId;
        $this->force = $force;
        $this->episodeType = $episodeType;
        
        // 使用專門的 queue 處理縮圖
        $this->onQueue('thumbnails');
    }

    /**
     * Execute the job.
     */
    public function handle(VideoThumbnailManager $thumbnailManager): void
    {
        $startTime = microtime(true);
        
        try {
            // 根據類型查找影片集數
            if ($this->episodeType === 'program') {
                $episode = ProgramEpisode::find($this->episodeId);
            } else {
                $episode = DramaEpisode::find($this->episodeId);
            }
            
            if (!$episode) {
                // 這種錯誤不應該重試
                Log::error('GenerateVideoThumbnail: 找不到影片集數', [
                    'episode_id' => $this->episodeId,
                    'episode_type' => $this->episodeType,
                    'attempt' => $this->attempts()
                ]);
                $this->fail(new ThumbnailGenerationException(
                    'Episode not found',
                    $this->episodeId
                ));
                return;
            }

            // 如果已有縮圖且不強制重新生成，則跳過
            // 注意：當 force = true 時，VideoThumbnailManager::generateThumbnail 會先刪除舊縮圖
            if (!$this->force && $episode->thumbnail) {
                Log::info('GenerateVideoThumbnail: 縮圖已存在，跳過生成', [
                    'episode_id' => $this->episodeId,
                    'force' => $this->force
                ]);
                return;
            }

            Log::info('GenerateVideoThumbnail: 開始生成縮圖', [
                'episode_id' => $this->episodeId,
                'video_type' => $episode->video_type,
                'force' => $this->force,
                'attempt' => $this->attempts()
            ]);

            // 生成縮圖
            $result = $thumbnailManager->generateThumbnail($episode);

            if ($result) {
                $executionTime = round(microtime(true) - $startTime, 2);
                Log::info('GenerateVideoThumbnail: 縮圖生成成功', [
                    'episode_id' => $this->episodeId,
                    'execution_time' => $executionTime . ' seconds',
                    'attempt' => $this->attempts()
                ]);
            } else {
                throw new ThumbnailGenerationException(
                    'Failed to generate thumbnail',
                    $this->episodeId,
                    $episode->video_type
                );
            }

        } catch (ThumbnailGenerationException $e) {
            // 處理自訂例外
            $this->handleThumbnailException($e);
            
        } catch (\Exception $e) {
            // 處理其他例外
            $this->handleGenericException($e);
        }
    }

    /**
     * 處理縮圖生成例外
     */
    protected function handleThumbnailException(ThumbnailGenerationException $e): void
    {
        Log::error('GenerateVideoThumbnail: 縮圖生成例外', $e->report());

        // 檢查是否應該重試
        if (!$e->shouldRetry()) {
            Log::warning('GenerateVideoThumbnail: 錯誤不可重試，標記任務失敗', [
                'episode_id' => $this->episodeId,
                'reason' => $e->getMessage()
            ]);
            $this->fail($e);
            return;
        }

        // 如果還有重試次數，則重新拋出例外觸發重試
        if ($this->attempts() < $this->tries) {
            Log::info('GenerateVideoThumbnail: 準備重試', [
                'episode_id' => $this->episodeId,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries
            ]);
            $this->release($this->calculateBackoff());
            throw $e;
        }

        // 已達最大重試次數
        $this->fail($e);
    }

    /**
     * 處理一般例外
     */
    protected function handleGenericException(\Exception $e): void
    {
        Log::error('GenerateVideoThumbnail: 未預期的錯誤', [
            'episode_id' => $this->episodeId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'attempt' => $this->attempts()
        ]);

        // 如果還有重試次數，則重新拋出例外觸發重試
        if ($this->attempts() < $this->tries) {
            $this->release($this->calculateBackoff());
            throw $e;
        }

        // 已達最大重試次數
        $this->fail($e);
    }

    /**
     * 處理失敗的任務
     */
    public function failed(Throwable $exception): void
    {
        // 記錄最終失敗
        Log::critical('GenerateVideoThumbnail: 任務最終失敗', [
            'episode_id' => $this->episodeId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'exception_class' => get_class($exception)
        ]);

        // 記錄到失敗任務表（如果有的話）
        $this->recordFailure($exception);
    }

    /**
     * 計算重試延遲時間（智能退避）
     */
    protected function calculateBackoff(): int
    {
        $attempt = $this->attempts();
        
        // 根據錯誤類型調整延遲
        if ($this->job && method_exists($this->job, 'payload')) {
            $payload = $this->job->payload();
            $lastException = $payload['exception'] ?? null;
            
            // 網路相關錯誤：較短延遲
            if (str_contains($lastException, 'network') || 
                str_contains($lastException, 'timeout')) {
                return $attempt * 10; // 10, 20, 30 秒
            }
            
            // FFMpeg 錯誤：較長延遲
            if (str_contains($lastException, 'FFMpeg')) {
                return $attempt * 60; // 1, 2, 3 分鐘
            }
        }
        
        // 預設：指數退避
        return min(30 * pow(2, $attempt - 1), 300); // 30, 60, 120, 240, 最多 300 秒
    }

    /**
     * 記錄失敗資訊
     */
    protected function recordFailure(Throwable $exception): void
    {
        try {
            // 可以記錄到資料庫或其他儲存
            $failureData = [
                'job_class' => self::class,
                'episode_id' => $this->episodeId,
                'error_message' => $exception->getMessage(),
                'error_class' => get_class($exception),
                'attempts' => $this->attempts(),
                'failed_at' => now(),
                'context' => [
                    'force' => $this->force,
                    'queue' => $this->queue,
                ]
            ];
            
            // 儲存到 cache 或資料庫
            cache()->put(
                "thumbnail_failure_{$this->episodeId}",
                $failureData,
                now()->addDays(7)
            );
            
        } catch (\Exception $e) {
            Log::error('無法記錄失敗資訊', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 取得任務的唯一 ID（用於防止重複）
     */
    public function uniqueId(): string
    {
        return "episode_{$this->episodeId}";
    }

    /**
     * 定義任務的標籤（用於監控）
     */
    public function tags(): array
    {
        return [
            'thumbnail',
            'episode:' . $this->episodeId,
            $this->force ? 'force' : 'normal'
        ];
    }
}
