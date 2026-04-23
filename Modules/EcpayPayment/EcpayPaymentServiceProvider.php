<?php

namespace Modules\EcpayPayment;

use Illuminate\Support\ServiceProvider;

class EcpayPaymentServiceProvider extends ServiceProvider
{
    /**
     * 模組名稱
     */
    protected string $moduleName = 'EcpayPayment';

    /**
     * 模組路徑
     */
    protected string $modulePath;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->modulePath = base_path('Modules/EcpayPayment');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerCommands();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * 註冊模組的 migrations
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom($this->modulePath . '/Database/Migrations');
    }

    /**
     * 註冊模組的 Artisan 指令
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\EcpayPayment\Console\Commands\IssueInvoicesCommand::class,
            ]);
        }
    }
}
