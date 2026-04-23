<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CnaRssService;
use Illuminate\Support\Facades\Log;

class SyncCnaRss extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cna:sync 
                            {--feed= : 指定要同步的 Feed (article_feed_cfp_photos, article_feed_cfp, article_feed_photos)}
                            {--limit= : 限制處理的文章數量}
                            {--force : 強制更新所有文章，忽略更新檢查}
                            {--detail : 顯示詳細輸出}
                            {--debug= : 測試特定文章ID的XML資料 (例如: --debug=3307 或 --debug=3307,3308,3309)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步 CNA RSS 新聞內容到系統';

    /**
     * CNA RSS Service
     *
     * @var CnaRssService
     */
    protected $cnaRssService;

    /**
     * Create a new command instance.
     */
    public function __construct(CnaRssService $cnaRssService)
    {
        parent::__construct();
        $this->cnaRssService = $cnaRssService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info(' CNA RSS 同步作業開始');
        $this->info('========================================');
        $this->newLine();

        // 取得選項
        $feedType = $this->option('feed');
        $limit = $this->option('limit');
        $force = $this->option('force');
        $verbose = $this->option('detail');
        $debugIds = $this->option('debug');

        // 設定 Feed URL
        if ($feedType) {
            $feeds = config('cna.feeds');
            if (!isset($feeds[$feedType])) {
                $this->error("無效的 Feed 類型：{$feedType}");
                $this->info('可用的 Feed 類型：');
                foreach ($feeds as $key => $url) {
                    $this->line("  - {$key}");
                }
                return Command::FAILURE;
            }
            $feedUrl = $feeds[$feedType];
            $this->info("使用指定 Feed：{$feedType}");
        } else {
            // 使用預設 Feed
            $defaultFeed = config('cna.default_feed', 'article_feed_cfp_photos');
            $feedUrl = config("cna.feeds.{$defaultFeed}");
            $this->info("使用預設 Feed：{$defaultFeed}");
        }

        $this->info("Feed URL：{$feedUrl}");
        $this->newLine();

        // 顯示同步選項
        if ($verbose) {
            $this->table(
                ['選項', '值'],
                [
                    ['處理限制', $limit ?? '無限制'],
                    ['強制更新', $force ? '是' : '否'],
                    ['詳細輸出', '是'],
                ]
            );
            $this->newLine();
        }

        try {
            // 開始同步
            $this->info('正在連接 RSS Feed...');
            
            $startTime = microtime(true);
            $result = $this->cnaRssService->syncFromFeed([
                'feed_url' => $feedUrl,
                'limit' => $limit,
                'force' => $force,
                'verbose' => $verbose,
                'output' => $this,  // 傳遞 Command 實例以便輸出進度
                'debug_ids' => $debugIds ? explode(',', $debugIds) : [],  // 傳遞要測試的文章 ID
            ]);
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $this->newLine();
            $this->info('========================================');
            $this->info(' 同步完成 - ' . now()->format('Y-m-d H:i:s'));
            $this->info('========================================');
            
            // 顯示統計結果
            $this->table(
                ['統計項目', '數值'],
                [
                    ['處理文章數', $result['processed'] ?? 0],
                    ['新增文章數', $result['created'] ?? 0],
                    ['更新文章數', $result['updated'] ?? 0],
                    ['略過文章數', $result['skipped'] ?? 0],
                    ['錯誤數量', $result['errors'] ?? 0],
                    ['執行時間', "{$executionTime} 秒"],
                ]
            );

            // 顯示錯誤訊息（如果有）
            if (!empty($result['error_messages'])) {
                $this->newLine();
                $this->error('錯誤訊息：');
                foreach ($result['error_messages'] as $error) {
                    $this->line("  - {$error}");
                }
            }


            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('同步過程發生錯誤：');
            $this->error($e->getMessage());
            
            if ($verbose) {
                $this->error('錯誤堆疊：');
                $this->line($e->getTraceAsString());
            }

            // 記錄錯誤到日誌
            Log::error('CNA RSS 同步失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}