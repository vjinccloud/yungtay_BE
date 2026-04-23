<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 生成 Sitemap 指令
 *
 * 用於手動或排程生成靜態 Sitemap XML 檔案
 *
 * 使用方式：
 * - 手動執行：php artisan sitemap:generate
 * - 排程執行：在 routes/console.php 設定
 *
 * 生成的檔案存放於：storage/app/public/sitemaps/
 */
class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成靜態 Sitemap XML 檔案（支援分語系、分檔處理大量資料）';

    /**
     * Execute the console command.
     */
    public function handle(SitemapService $sitemapService): int
    {
        $this->info('開始生成 Sitemap...');
        $this->newLine();

        $startTime = microtime(true);

        try {
            $files = $sitemapService->generateAll();

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info('✅ Sitemap 生成完成！');
            $this->newLine();

            $this->table(
                ['檔案名稱'],
                array_map(fn($file) => [$file], $files)
            );

            $this->newLine();
            $this->info("總共生成 " . count($files) . " 個檔案");
            $this->info("執行時間：{$duration} 秒");
            $this->newLine();

            // 顯示存取路徑
            $this->comment('Sitemap 存放路徑：storage/app/public/sitemaps/');
            $this->comment('公開存取 URL：' . url('/sitemap.xml'));
            $this->comment('子 Sitemap URL：' . url('/sitemaps/{filename}.xml'));

            // 寫入 Log
            Log::info('Sitemap generation completed', [
                'files_count' => count($files),
                'duration' => $duration . ' 秒',
                'files' => $files,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Sitemap 生成失敗：' . $e->getMessage());
            $this->newLine();
            $this->error($e->getTraceAsString());

            // 寫入錯誤 Log
            Log::error('Sitemap generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}