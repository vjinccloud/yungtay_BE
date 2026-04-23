<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\BasicWebsiteSettingService;
use Illuminate\Support\Facades\Cache;

class FrontendServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $websiteSettingService = app(BasicWebsiteSettingService::class);    
        // 使用 View Composer 提供網站設定給所有前台視圖
        View::composer('frontend.*', function ($view) use ($websiteSettingService) {
            $siteInfo = $websiteSettingService->getFrontendSettings();
            $view->with('siteInfo', $siteInfo);
        });
    }
}