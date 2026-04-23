<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * 一鍵設定所有模組
 *
 * 功能：
 * 1. 執行所有模組的 migrations
 * 2. 執行所有模組的 seed-admin-menu 指令
 * 3. 執行所有模組的 grant-permissions 指令
 * 4. 同步權限（module:install --sync-permissions）
 *
 * 使用方式：
 * php artisan modules:setup                # 執行 migrate + seed + permissions
 * php artisan modules:setup --migrate-only # 只跑 migration
 * php artisan modules:setup --seed-only    # 只跑 seed 指令
 * php artisan modules:setup --fresh        # 先 migrate:fresh 再 seed
 * php artisan modules:setup --force        # seed 時加上 --force 參數
 */
class ModulesSetupCommand extends Command
{
    protected $signature = 'modules:setup
                            {--migrate-only : 只執行 migrations}
                            {--seed-only : 只執行 seed 指令}
                            {--fresh : 使用 migrate:fresh（⚠️ 會清空所有資料表）}
                            {--force : seed 指令加上 --force 參數（強制重新寫入）}';

    protected $description = '一鍵執行所有模組的 migrations 與 seeders';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║       Modules Setup Command          ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $migrateOnly = $this->option('migrate-only');
        $seedOnly = $this->option('seed-only');

        // Step 1: Migrations
        if (!$seedOnly) {
            $this->runMigrations();
        }

        // Step 2: Seed commands
        if (!$migrateOnly) {
            $this->runSeedCommands();
            $this->runGrantPermissions();
            $this->runSyncPermissions();
        }

        // Step 5: Ziggy 路由產生
        $this->runZiggyGenerate();

        // Step 6: 清除快取
        $this->clearCaches();

        $this->newLine();
        $this->info('🎉 所有模組設定完成！請重新整理後台頁面。');
        $this->newLine();

        return 0;
    }

    /**
     * 執行所有模組的 migrations
     */
    protected function runMigrations(): void
    {
        $this->info('━━━ Step 1: 執行 Migrations ━━━');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  使用 migrate:fresh，所有資料表將被清空！');
            if (!$this->confirm('確定要繼續嗎？', false)) {
                $this->info('已取消 migrate:fresh');
                return;
            }
            $this->call('migrate:fresh');
        } else {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('✅ Migrations 完成');
        $this->newLine();
    }

    /**
     * 探索並執行所有模組的 seed-admin-menu 指令
     */
    protected function runSeedCommands(): void
    {
        $this->info('━━━ Step 2: 執行模組 Seed 指令 ━━━');
        $this->newLine();

        $seedCommands = $this->discoverCommands('SeedAdminMenuCommand');

        if (empty($seedCommands)) {
            $this->line('   沒有找到任何 seed 指令');
            return;
        }

        foreach ($seedCommands as $moduleName => $commandClass) {
            $this->info("📦 模組：{$moduleName}");

            try {
                // 從 command class 取得 signature
                $command = app($commandClass);
                $commandName = $command->getName();

                $params = [];
                if ($this->option('force')) {
                    $params['--force'] = true;
                }

                $this->call($commandName, $params);
            } catch (\Exception $e) {
                $this->error("   ❌ 執行失敗：{$e->getMessage()}");
            }

            $this->newLine();
        }

        $this->info('✅ Seed 指令完成');
        $this->newLine();
    }

    /**
     * 探索並執行所有模組的 grant-permissions 指令
     */
    protected function runGrantPermissions(): void
    {
        $this->info('━━━ Step 3: 執行模組 Grant Permissions ━━━');
        $this->newLine();

        $grantCommands = $this->discoverCommands('GrantPermissionsCommand');

        if (empty($grantCommands)) {
            $this->line('   沒有找到任何 grant-permissions 指令');
            return;
        }

        foreach ($grantCommands as $moduleName => $commandClass) {
            $this->info("🔑 模組：{$moduleName}");

            try {
                $command = app($commandClass);
                $commandName = $command->getName();

                $this->call($commandName);
            } catch (\Exception $e) {
                $this->error("   ❌ 執行失敗：{$e->getMessage()}");
            }

            $this->newLine();
        }

        $this->info('✅ Grant Permissions 完成');
        $this->newLine();
    }

    /**
     * 同步權限（從選單 → 權限表）
     */
    protected function runSyncPermissions(): void
    {
        $this->info('━━━ Step 4: 同步權限 ━━━');
        $this->newLine();

        try {
            $this->call('module:install', ['--sync-permissions' => true]);
        } catch (\Exception $e) {
            $this->warn("   ⚠️  同步權限時發生問題：{$e->getMessage()}");
        }

        $this->newLine();
        $this->info('✅ 權限同步完成');
        $this->newLine();
    }

    /**
     * 從 Modules 各模組的 Database 目錄中探索指定名稱的 Command
     *
     * @return array<string, string> [moduleName => className]
     */
    protected function discoverCommands(string $commandFileName): array
    {
        $modulesPath = base_path('Modules');
        $commands = [];

        if (!is_dir($modulesPath)) {
            return $commands;
        }

        foreach (File::directories($modulesPath) as $moduleDir) {
            $filePath = $moduleDir . '/Database/' . $commandFileName . '.php';

            if (file_exists($filePath)) {
                $moduleName = basename($moduleDir);
                $className = 'Modules\\' . $moduleName . '\\Database\\' . $commandFileName;

                if (class_exists($className)) {
                    $commands[$moduleName] = $className;
                }
            }
        }

        return $commands;
    }

    /**
     * 產生 Ziggy 路由檔案
     */
    protected function runZiggyGenerate(): void
    {
        $this->info('━━━ Step 5: 產生 Ziggy 路由 ━━━');
        $this->newLine();

        try {
            $this->call('ziggy:generate');
        } catch (\Exception $e) {
            $this->warn("   ⚠️  Ziggy 產生失敗：{$e->getMessage()}");
        }

        $this->newLine();
        $this->info('✅ Ziggy 路由產生完成');
        $this->newLine();
    }

    /**
     * 清除所有快取
     */
    protected function clearCaches(): void
    {
        $this->info('━━━ Step 6: 清除快取 ━━━');
        $this->newLine();

        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        $this->newLine();
        $this->info('✅ 快取清除完成');
        $this->newLine();
    }
}
