<?php
// config/ffmpeg.php

return [
    /*
    |--------------------------------------------------------------------------
    | FFMpeg 二進位檔案路徑
    |--------------------------------------------------------------------------
    |
    | 指定 FFMpeg 和 FFProbe 的執行檔路徑
    | 可以根據環境變數動態設定
    |
    */
    'binaries' => [
        'ffmpeg' => env('FFMPEG_BINARY', PHP_OS_FAMILY === 'Windows' 
            ? 'C:/ffmpeg/bin/ffmpeg.exe' 
            : '/usr/bin/ffmpeg'),
        
        'ffprobe' => env('FFPROBE_BINARY', PHP_OS_FAMILY === 'Windows' 
            ? 'C:/ffmpeg/bin/ffprobe.exe' 
            : '/usr/bin/ffprobe'),
    ],

    /*
    |--------------------------------------------------------------------------
    | FFMpeg 執行設定
    |--------------------------------------------------------------------------
    */
    'timeout' => env('FFMPEG_TIMEOUT', 300),  // 5 分鐘
    'threads' => env('FFMPEG_THREADS', 4),

    /*
    |--------------------------------------------------------------------------
    | 縮圖設定
    |--------------------------------------------------------------------------
    */
    'thumbnail' => [
        'width' => env('THUMBNAIL_WIDTH', 310),
        'height' => env('THUMBNAIL_HEIGHT', 175),
        'quality' => env('THUMBNAIL_QUALITY', 80),
        'format' => 'jpg',
        
        // 影片擷取時間（秒）
        'capture_time' => env('THUMBNAIL_CAPTURE_TIME', 5),
        
        // 儲存路徑（相對於 storage/app/public）
        'storage_path' => 'dramas/thumbnails',
        
        // 縮圖儲存磁碟
        'disk' => env('THUMBNAIL_DISK', 'public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | YouTube 設定
    |--------------------------------------------------------------------------
    */
    'youtube' => [
        // YouTube 縮圖解析度優先順序
        'thumbnail_qualities' => [
            'maxresdefault',  // 1280x720
            'sddefault',      // 640x480
            'hqdefault',      // 480x360
            'mqdefault',      // 320x180
        ],
        
        // HTTP 請求設定
        'http_options' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'verify_ssl' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 同步生成設定
    |--------------------------------------------------------------------------
    |
    | 設定為 true 時，縮圖會立即生成（同步執行）
    | 設定為 false 時，縮圖會透過隊列生成（非同步執行）
    | 建議設為 true，避免前端顯示時沒有縮圖
    |
    */
    'sync_generation' => env('THUMBNAIL_SYNC_GENERATION', true),

    /*
    |--------------------------------------------------------------------------
    | Queue 設定
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => env('THUMBNAIL_QUEUE_ENABLED', true),
        'queue_name' => env('THUMBNAIL_QUEUE_NAME', 'thumbnails'),
        'retry_times' => env('THUMBNAIL_RETRY_TIMES', 3),
        'retry_delay' => [30, 60, 120],  // 重試延遲（秒）
        'process_delay' => env('THUMBNAIL_PROCESS_DELAY', 5),  // 處理延遲（秒）
    ],

    /*
    |--------------------------------------------------------------------------
    | 錯誤處理
    |--------------------------------------------------------------------------
    */
    'fallback' => [
        // 當 FFMpeg 套件失敗時，是否嘗試使用 shell 命令
        'use_shell_command' => env('FFMPEG_USE_SHELL_FALLBACK', true),
        
        // 是否記錄詳細錯誤
        'log_errors' => env('THUMBNAIL_LOG_ERRORS', true),
        
        // 預設縮圖（當生成失敗時使用）
        'default_thumbnail' => '/frontend/images/default_video.png',
    ],
];