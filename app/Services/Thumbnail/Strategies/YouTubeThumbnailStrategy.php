<?php
// app/Services/Thumbnail/Strategies/YouTubeThumbnailStrategy.php

namespace App\Services\Thumbnail\Strategies;

use App\Models\DramaEpisode;
use App\Models\ProgramEpisode;
use App\Services\Thumbnail\Contracts\ThumbnailStrategyInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class YouTubeThumbnailStrategy implements ThumbnailStrategyInterface
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
        return $episode->video_type === 'youtube' && !empty($episode->youtube_url);
    }

    /**
     * 生成縮圖
     * @param DramaEpisode|ProgramEpisode $episode
     */
    public function generate($episode): ?string
    {
        $videoId = $this->extractYouTubeVideoId($episode->youtube_url);
        
        if (!$videoId) {
            Log::warning('YouTubeThumbnailStrategy: 無法解析 YouTube ID', [
                'episode_id' => $episode->id,
                'url' => $episode->youtube_url
            ]);
            return null;
        }

        // 嘗試不同解析度的縮圖
        $qualities = config('ffmpeg.youtube.thumbnail_qualities', [
            'maxresdefault', 'sddefault', 'hqdefault', 'mqdefault'
        ]);

        foreach ($qualities as $quality) {
            $thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
            
            try {
                // 下載並處理圖片
                $imageData = $this->downloadImage($thumbnailUrl);
                
                if ($imageData) {
                    return $this->processAndSaveImage($imageData, $episode);
                }
            } catch (\Exception $e) {
                // 嘗試下一個解析度
                continue;
            }
        }

        Log::error('YouTubeThumbnailStrategy: 無法取得 YouTube 縮圖', [
            'episode_id' => $episode->id,
            'video_id' => $videoId
        ]);

        return null;
    }

    /**
     * 取得策略名稱
     */
    public function getName(): string
    {
        return 'youtube';
    }

    /**
     * 從 YouTube URL 提取影片 ID
     */
    protected function extractYouTubeVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * 下載圖片
     */
    protected function downloadImage(string $url): ?string
    {
        $httpOptions = config('ffmpeg.youtube.http_options');
        
        try {
            $userAgent = $httpOptions['user_agent'] ?? 'Mozilla/5.0';
            $timeout   = $httpOptions['timeout'] ?? 5;
            $verify    = $httpOptions['verify_ssl'] ?? true;

            $res = Http::withHeaders(['User-Agent' => $userAgent])
                ->timeout($timeout)
                ->retry(2, 200)
                ->withOptions(['verify' => $verify])
                ->get($url);

            if ($res->successful()) {
                return $res->body();
            }
        } catch (\Throwable $e) {
            // fallback 到原本的 file_get_contents
            Log::debug('YouTubeThumbnailStrategy: Http 下載失敗，改用 file_get_contents', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => ['User-Agent: ' . ($httpOptions['user_agent'] ?? 'Mozilla/5.0')],
                'timeout' => $httpOptions['timeout'] ?? 5,
            ],
            'ssl' => [
                'verify_peer'      => $httpOptions['verify_ssl'] ?? true,
                'verify_peer_name' => $httpOptions['verify_ssl'] ?? true,
                'allow_self_signed'=> !($httpOptions['verify_ssl'] ?? true),
            ],
        ]);

        $imageData = @file_get_contents($url, false, $context);
        return $imageData !== false ? $imageData : null;
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
            
            // 生成檔案路徑（可被呼叫端覆蓋）
            $type = $episode instanceof ProgramEpisode ? 'program' : 'drama';

            // 允許呼叫端透過 $episode->storage_subdir / ->filename_prefix 覆蓋
            $customSubdir  = property_exists($episode, 'storage_subdir') ? trim((string)$episode->storage_subdir, '/') : null;
            $customPrefix  = property_exists($episode, 'filename_prefix') ? (string)$episode->filename_prefix : null;

            $subPath = $customSubdir ?: ($type === 'program' ? 'programs/thumbnails' : 'dramas/thumbnails');
            $prefix  = $customPrefix ?: "{$type}_episode_{$episode->id}_thumb";
            $filename = $prefix . '_' . time() . '.jpg';
            $path = $subPath . '/' . $filename;
            
            // 使用 Storage Facade 儲存
            $disk = Storage::disk(config('ffmpeg.thumbnail.disk', 'public'));
            $quality = config('ffmpeg.thumbnail.quality', 80);
            
            // 編碼圖片
            $encodedImage = $image->toJpeg($quality);
            
            // 儲存到 Storage
            if ($disk->put($path, $encodedImage)) {
                Log::info('YouTubeThumbnailStrategy: 縮圖儲存成功', [
                    'episode_id' => $episode->id,
                    'path' => $path
                ]);
                return $path;
            }
            
        } catch (\Exception $e) {
            Log::error('YouTubeThumbnailStrategy: 處理圖片失敗', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
}