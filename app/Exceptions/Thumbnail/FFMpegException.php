<?php
// app/Exceptions/Thumbnail/FFMpegException.php

namespace App\Exceptions\Thumbnail;

class FFMpegException extends ThumbnailGenerationException
{
    /**
     * FFMpeg 不可用
     */
    public static function notAvailable(string $binaryPath): self
    {
        return new self(
            "FFMpeg binary not available at: {$binaryPath}",
            null,
            null,
            ['binary_path' => $binaryPath]
        );
    }

    /**
     * FFMpeg 執行失敗
     */
    public static function executionFailed(string $command, string $output): self
    {
        return new self(
            "FFMpeg execution failed",
            null,
            null,
            [
                'command' => $command,
                'output' => $output
            ]
        );
    }

    /**
     * 影片處理超時
     */
    public static function timeout(int $episodeId, int $timeout): self
    {
        return new self(
            "FFMpeg processing timeout after {$timeout} seconds",
            $episodeId,
            'upload',
            ['timeout' => $timeout]
        );
    }
}