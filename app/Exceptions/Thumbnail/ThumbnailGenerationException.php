<?php
// app/Exceptions/Thumbnail/ThumbnailGenerationException.php

namespace App\Exceptions\Thumbnail;

use Exception;

class ThumbnailGenerationException extends Exception
{
    protected $episodeId;
    protected $videoType;
    protected $context = [];

    public function __construct(
        string $message,
        int $episodeId = null,
        string $videoType = null,
        array $context = [],
        int $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->episodeId = $episodeId;
        $this->videoType = $videoType;
        $this->context = $context;
    }

    /**
     * 取得影片 ID
     */
    public function getEpisodeId(): ?int
    {
        return $this->episodeId;
    }

    /**
     * 取得影片類型
     */
    public function getVideoType(): ?string
    {
        return $this->videoType;
    }

    /**
     * 取得上下文資訊
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 判斷是否應該重試
     */
    public function shouldRetry(): bool
    {
        // 某些錯誤不應該重試
        $nonRetryableMessages = [
            'Invalid video format',
            'Video file not found',
            'Invalid YouTube URL',
            'Episode not found',
        ];

        foreach ($nonRetryableMessages as $message) {
            if (str_contains($this->getMessage(), $message)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 取得錯誤報告
     */
    public function report(): array
    {
        return [
            'message' => $this->getMessage(),
            'episode_id' => $this->episodeId,
            'video_type' => $this->videoType,
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ];
    }
}