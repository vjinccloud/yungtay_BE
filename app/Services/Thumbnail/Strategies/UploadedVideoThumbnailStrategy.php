<?php
// app/Services/Thumbnail/Strategies/UploadedVideoThumbnailStrategy.php

namespace App\Services\Thumbnail\Strategies;

use App\Models\DramaEpisode;
use App\Models\ProgramEpisode;
use App\Services\Thumbnail\Contracts\ThumbnailStrategyInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;

class UploadedVideoThumbnailStrategy implements ThumbnailStrategyInterface
{
    /**
     * @var ImageManager
     */
    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * 判斷是否支援此影片類型
     * @param DramaEpisode|ProgramEpisode $episode
     */
    public function supports($episode): bool
    {
        return $episode->video_type === 'upload' && !empty($episode->video_file_path);
    }

    /**
     * 生成縮圖
     * @param DramaEpisode|ProgramEpisode $episode
     */
    public function generate($episode): ?string
    {
        // 取得影片檔案的完整路徑
        $videoPath = Storage::disk('public')->path($episode->video_file_path);
        
        if (!file_exists($videoPath)) {
            Log::warning('UploadedVideoThumbnailStrategy: 影片檔案不存在', [
                'episode_id' => $episode->id,
                'path' => $videoPath
            ]);
            return null;
        }

        // 嘗試使用 FFMpeg 套件
        $frameData = $this->extractFrameWithFFMpeg($videoPath);
        
        // 如果 FFMpeg 套件失敗，嘗試使用 shell 命令
        if (!$frameData && config('ffmpeg.fallback.use_shell_command')) {
            $frameData = $this->extractFrameWithShell($videoPath);
        }
        
        if (!$frameData) {
            Log::error('UploadedVideoThumbnailStrategy: 無法擷取影片畫面', [
                'episode_id' => $episode->id,
                'video_path' => $episode->video_file_path
            ]);
            return null;
        }

        return $this->processAndSaveImage($frameData, $episode);
    }

    /**
     * 取得策略名稱
     */
    public function getName(): string
    {
        return 'upload';
    }

    /**
     * 使用 FFMpeg 套件擷取畫面
     */
    protected function extractFrameWithFFMpeg(string $videoPath): ?string
    {
        try {
            $config = [
                'ffmpeg.binaries'  => config('ffmpeg.binaries.ffmpeg'),
                'ffprobe.binaries' => config('ffmpeg.binaries.ffprobe'),
                'timeout'          => config('ffmpeg.timeout'),
                'ffmpeg.threads'   => config('ffmpeg.threads'),
            ];

            $ffmpeg = FFMpeg::create($config);
            $video = $ffmpeg->open($videoPath);
            
            // 擷取指定秒數的畫面
            $captureTime = config('ffmpeg.thumbnail.capture_time', 5);
            $frame = $video->frame(TimeCode::fromSeconds($captureTime));
            
            // 生成臨時檔案
            $tempFile = sys_get_temp_dir() . '/frame_' . uniqid() . '.jpg';
            $frame->save($tempFile);
            
            if (file_exists($tempFile)) {
                $data = file_get_contents($tempFile);
                unlink($tempFile);
                return $data;
            }
            
        } catch (\Exception $e) {
            Log::warning('UploadedVideoThumbnailStrategy: FFMpeg 套件執行失敗', [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * 使用 Shell 命令擷取畫面（備用方案）
     */
    protected function extractFrameWithShell(string $videoPath): ?string
    {
        // 記錄可用的執行函數
        Log::info('UploadedVideoThumbnailStrategy: 檢查可用的執行函數', [
            'proc_open' => function_exists('proc_open'),
            'exec' => function_exists('exec'),
            'shell_exec' => function_exists('shell_exec'),
            'system' => function_exists('system'),
            'passthru' => function_exists('passthru'),
        ]);
        
        // 嘗試多種執行方式
        $ffmpegBinary = config('ffmpeg.binaries.ffmpeg');
        $captureTime = config('ffmpeg.thumbnail.capture_time', 5);
        $tempFile = sys_get_temp_dir() . '/frame_' . uniqid() . '.jpg';
        
        // 構建 ffmpeg 命令
        $command = sprintf(
            '%s -i %s -ss 00:00:%02d -vframes 1 -f image2 %s 2>&1',
            escapeshellcmd($ffmpegBinary),
            escapeshellarg($videoPath),
            $captureTime,
            escapeshellarg($tempFile)
        );
        
        Log::info('UploadedVideoThumbnailStrategy: 準備執行 FFmpeg 命令', [
            'command' => $command,
            'ffmpeg_binary' => $ffmpegBinary,
            'temp_file' => $tempFile
        ]);
        
        $output = null;
        $executed = false;
        
        // 方法 1: 使用 proc_open (最可靠)
        if (function_exists('proc_open') && !$executed) {
            $descriptorspec = [
                0 => ["pipe", "r"],  // stdin
                1 => ["pipe", "w"],  // stdout
                2 => ["pipe", "w"]   // stderr
            ];
            
            $process = proc_open($command, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $return_value = proc_close($process);
                $executed = true;
                Log::info('UploadedVideoThumbnailStrategy: 使用 proc_open 執行 FFmpeg', [
                    'return_value' => $return_value,
                    'output_length' => strlen($output),
                    'error' => substr($error, 0, 500)  // 只記錄前 500 字元的錯誤
                ]);
            } else {
                Log::warning('UploadedVideoThumbnailStrategy: proc_open 失敗');
            }
        }
        
        // 方法 2: 使用 exec
        if (!$executed && function_exists('exec')) {
            exec($command, $output_array, $return_value);
            $output = implode("\n", $output_array);
            $executed = true;
            Log::info('UploadedVideoThumbnailStrategy: 使用 exec 執行 FFmpeg');
        }
        
        // 方法 3: 使用 shell_exec
        if (!$executed && function_exists('shell_exec')) {
            $output = shell_exec($command);
            $executed = true;
            Log::info('UploadedVideoThumbnailStrategy: 使用 shell_exec 執行 FFmpeg');
        }
        
        // 方法 4: 使用 system
        if (!$executed && function_exists('system')) {
            ob_start();
            system($command, $return_value);
            $output = ob_get_clean();
            $executed = true;
            Log::info('UploadedVideoThumbnailStrategy: 使用 system 執行 FFmpeg');
        }
        
        if (!$executed) {
            Log::warning('UploadedVideoThumbnailStrategy: 所有執行方法都不可用');
            return null;
        }
        
        // 檢查輸出檔案
        if (file_exists($tempFile) && filesize($tempFile) > 0) {
            $data = file_get_contents($tempFile);
            unlink($tempFile);
            return $data;
        }
        
        Log::warning('UploadedVideoThumbnailStrategy: Shell 命令執行失敗', [
            'command' => $command,
            'output' => $output
        ]);
        
        return null;
    }

    /**
     * 處理並儲存圖片
     * @param string $imageData
     * @param DramaEpisode|ProgramEpisode $episode
     */
    protected function processAndSaveImage(string $imageData, $episode): ?string
    {
        try {
            // 讀取圖片
            $image = $this->imageManager->read($imageData);
            
            // 調整尺寸
            $width = config('ffmpeg.thumbnail.width');
            $height = config('ffmpeg.thumbnail.height');
            $image->cover($width, $height);
            
            // 生成檔案路徑（區分影音和節目）
            $type = $episode instanceof ProgramEpisode ? 'program' : 'drama';
            $filename = "{$type}_episode_{$episode->id}_thumb_" . time() . ".jpg";
            // 為節目使用 programs 子目錄，影音使用 dramas 子目錄
            $subPath = $type === 'program' ? 'programs/thumbnails' : 'dramas/thumbnails';
            $path = "{$subPath}/{$filename}";
            
            // 使用 Storage Facade 儲存
            $disk = Storage::disk(config('ffmpeg.thumbnail.disk', 'public'));
            $quality = config('ffmpeg.thumbnail.quality', 80);
            
            // 編碼圖片
            $encodedImage = $image->toJpeg($quality);
            
            // 儲存到 Storage
            if ($disk->put($path, $encodedImage)) {
                Log::info('UploadedVideoThumbnailStrategy: 縮圖儲存成功', [
                    'episode_id' => $episode->id,
                    'path' => $path
                ]);
                return $path;
            }
            
        } catch (\Exception $e) {
            Log::error('UploadedVideoThumbnailStrategy: 處理圖片失敗', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
}