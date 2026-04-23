<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
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
        // 僅在本地開發環境跳過 SSL 驗證
        if (app()->environment('local') && env('GUZZLE_VERIFY') === 'false') {
            config(['services.guzzle.verify' => false]);
        }
        
        // 註冊 LINE Socialite Provider
        \Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('line', \SocialiteProviders\Line\Provider::class);
        });

        // 註冊 Model Observers
        \App\Models\MailRecipient::observe(\App\Observers\MailRecipientObserver::class);
        \App\Models\CustomerService::observe(\App\Observers\CustomerServiceObserver::class);
        \App\Models\MemberNotification::observe(\App\Observers\MemberNotificationObserver::class);
    }
}
