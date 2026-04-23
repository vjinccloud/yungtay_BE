<?php

namespace App\Console\Commands;

use App\Jobs\GenerateVideoThumbnail;
use App\Models\DramaEpisode;
use Illuminate\Console\Command;

class ProcessVideoThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:process-thumbnails 
                            {--episode=* : 指定影片 ID}
                            {--type= : 影片類型 (youtube/upload)}
                            {--force : 強制重新生成}
                            {--missing : 只處理缺少縮圖的影片}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '使用 Queue 處理影片縮圖生成';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $episodeIds = $this->option('episode');
        $videoType = $this->option('type');
        $force = $this->option('force');
        $missingOnly = $this->option('missing');

        // 建立查詢
        $query = DramaEpisode::query();

        // 如果指定了影片 ID
        if (!empty($episodeIds)) {
            $query->whereIn('id', $episodeIds);
            $this->info('處理指定的 ' . count($episodeIds) . ' 個影片');
        }

        // 如果指定了影片類型
        if ($videoType) {
            $query->where('video_type', $videoType);
            $this->info('只處理 ' . $videoType . ' 類型的影片');
        }

        // 如果只處理缺少縮圖的
        if ($missingOnly) {
            $query->whereDoesntHave('thumbnail');
            $this->info('只處理缺少縮圖的影片');
        }

        // 取得影片列表
        $episodes = $query->get();

        if ($episodes->isEmpty()) {
            $this->warn('沒有找到需要處理的影片');
            return 0;
        }

        $this->info('找到 ' . $episodes->count() . ' 個影片需要處理');

        // 詢問確認
        if (!$this->confirm('確定要將這些影片加入縮圖生成佇列嗎？')) {
            $this->info('已取消');
            return 0;
        }

        $bar = $this->output->createProgressBar($episodes->count());
        $bar->start();

        $dispatched = 0;
        $skipped = 0;

        foreach ($episodes as $episode) {
            // 檢查是否需要處理
            if (!$force && $episode->thumbnail) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // 派發 Job 到 Queue
            GenerateVideoThumbnail::dispatch($episode->id, $force)
                ->onQueue('thumbnails')
                ->delay(now()->addSeconds($dispatched * 2)); // 每個任務延遲 2 秒，避免同時大量處理

            $dispatched++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("已將 {$dispatched} 個影片加入縮圖生成佇列");
        if ($skipped > 0) {
            $this->info("跳過 {$skipped} 個已有縮圖的影片（使用 --force 可強制重新生成）");
        }

        $this->newLine();
        $this->info('提示：請確保 Queue Worker 正在執行');
        $this->line('執行以下命令啟動 Worker：');
        $this->line('php artisan queue:work --queue=thumbnails --tries=3 --timeout=300');

        return 0;
    }
}
