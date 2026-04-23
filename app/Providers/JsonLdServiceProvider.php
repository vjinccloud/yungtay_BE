<?php

namespace App\Providers;

use App\Services\JsonLdService;
use Illuminate\Support\ServiceProvider;

class JsonLdServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('jsonld', function ($app) {
            return new JsonLdService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}