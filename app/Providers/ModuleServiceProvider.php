<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

/**
 * 模組自動探索 Service Provider
 *
 * 自動掃描 Modules/ 目錄，註冊所有模組的：
 * - Database/Migrations 或 Database/migrations（migration 檔案）
 * - Database/ 下的 Artisan Command（如 SeedAdminMenuCommand、GrantPermissionsCommand）
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerModuleMigrations();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerModuleCommands();
    }

    /**
     * 自動註冊所有模組的 migrations
     */
    /**
     * 已有自己 ServiceProvider 的模組，不重複載入
     */
    protected array $excludeModules = [
        'EcpayPayment', // 已由 EcpayPaymentServiceProvider 處理
    ];

    protected function registerModuleMigrations(): void
    {
        $modulesPath = base_path('Modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        $loaded = [];

        foreach (File::directories($modulesPath) as $moduleDir) {
            $moduleName = basename($moduleDir);

            // 跳過已有自己 ServiceProvider 的模組
            if (in_array($moduleName, $this->excludeModules)) {
                continue;
            }

            // 支援 Database/Migrations 和 Database/migrations 兩種命名
            // 用 realpath 去重（Windows 不分大小寫會指向同一目錄）
            foreach (['Database/Migrations', 'Database/migrations'] as $migrationDir) {
                $path = $moduleDir . '/' . $migrationDir;
                if (is_dir($path)) {
                    $real = realpath($path);
                    if (!in_array($real, $loaded)) {
                        $this->loadMigrationsFrom($path);
                        $loaded[] = $real;
                    }
                }
            }
        }
    }

    /**
     * 自動註冊所有模組的 Artisan Commands
     */
    protected function registerModuleCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $modulesPath = base_path('Modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        $commands = [];

        foreach (File::directories($modulesPath) as $moduleDir) {
            $databaseDir = $moduleDir . '/Database';

            if (!is_dir($databaseDir)) {
                continue;
            }

            // 掃描 Database/ 下所有 *Command.php
            foreach (File::files($databaseDir) as $file) {
                if (str_ends_with($file->getFilename(), 'Command.php')) {
                    $moduleName = basename($moduleDir);
                    $className = 'Modules\\' . $moduleName . '\\Database\\' . $file->getFilenameWithoutExtension();

                    if (class_exists($className)) {
                        $commands[] = $className;
                    }
                }
            }

            // 也掃描 Console/Commands/ 目錄
            $consoleDir = $moduleDir . '/Console/Commands';
            if (is_dir($consoleDir)) {
                foreach (File::files($consoleDir) as $file) {
                    if (str_ends_with($file->getFilename(), '.php')) {
                        $moduleName = basename($moduleDir);
                        $className = 'Modules\\' . $moduleName . '\\Console\\Commands\\' . $file->getFilenameWithoutExtension();

                        if (class_exists($className)) {
                            $commands[] = $className;
                        }
                    }
                }
            }
        }

        if (!empty($commands)) {
            $this->commands($commands);
        }
    }
}
