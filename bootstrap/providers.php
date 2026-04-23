<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FrontendServiceProvider::class,
    App\Providers\JsonLdServiceProvider::class,
    App\Providers\ThumbnailServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
    \SocialiteProviders\Manager\ServiceProvider::class,
    
    // 模組 Service Providers（自動探索所有模組的 migrations + commands）
    App\Providers\ModuleServiceProvider::class,
    Modules\EcpayPayment\EcpayPaymentServiceProvider::class,
];
