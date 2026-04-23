<?php
// app/Exceptions/Thumbnail/YouTubeException.php

namespace App\Exceptions\Thumbnail;

class YouTubeException extends ThumbnailGenerationException
{
    /**
     * 無效的 YouTube URL
     */
    public static function invalidUrl(string $url, int $episodeId): self
    {
        return new self(
            "Invalid YouTube URL: {$url}",
            $episodeId,
            'youtube',
            ['url' => $url]
        );
    }

    /**
     * 無法下載縮圖
     */
    public static function downloadFailed(string $videoId, int $episodeId): self
    {
        return new self(
            "Failed to download YouTube thumbnail for video: {$videoId}",
            $episodeId,
            'youtube',
            ['video_id' => $videoId]
        );
    }

    /**
     * 無法解析影片 ID
     */
    public static function cannotExtractVideoId(string $url, int $episodeId): self
    {
        return new self(
            "Cannot extract video ID from URL: {$url}",
            $episodeId,
            'youtube',
            ['url' => $url]
        );
    }
}