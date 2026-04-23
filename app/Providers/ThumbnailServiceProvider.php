<?php

namespace App\Providers;

use App\Services\Thumbnail\VideoThumbnailManager;
use Illuminate\Support\ServiceProvider;

class ThumbnailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 註冊 VideoThumbnailManager 為單例
        $this->app->singleton(VideoThumbnailManager::class, function ($app) {
            return new VideoThumbnailManager();
        });

        // 註冊別名
        $this->app->alias(VideoThumbnailManager::class, 'thumbnail.manager');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 發布設定檔
        $this->publishes([
            __DIR__.'/../../config/ffmpeg.php' => config_path('ffmpeg.php'),
        ], 'ffmpeg-config');
    }
}
