<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\GenerateVideoThumbnail;
use App\Models\DramaEpisode;

class MonitorThumbnailFailures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbnail:monitor-failures 
                            {--retry : 重試失敗的任務}
                            {--clear : 清除失敗記錄}
                            {--days=7 : 顯示最近幾天的失敗}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '監控縮圖生成失敗的任務';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clear')) {
            return $this->clearFailures();
        }

        if ($this->option('retry')) {
            return $this->retryFailures();
        }

        return $this->showFailures();
    }

    /**
     * 顯示失敗的任務
     */
    protected function showFailures()
    {
        $days = $this->option('days');
        
        $failures = DB::table('failed_jobs')
            ->where('payload', 'like', '%GenerateVideoThumbnail%')
            ->where('failed_at', '>=', now()->subDays($days))
            ->orderBy('failed_at', 'desc')
            ->get();

        if ($failures->isEmpty()) {
            $this->info("最近 {$days} 天沒有失敗的縮圖生成任務");
            return 0;
        }

        $this->info("最近 {$days} 天的失敗任務：");
        $this->newLine();

        // 分析失敗原因
        $stats = [
            'total' => $failures->count(),
            'by_error' => [],
            'by_episode' => []
        ];

        $headers = ['ID', 'Episode ID', '錯誤類型', '失敗時間', '重試次數'];
        $rows = [];

        foreach ($failures as $failure) {
            $payload = json_decode($failure->payload, true);
            $episodeId = $this->extractEpisodeId($payload);
            $exception = json_decode($failure->exception, true);
            $errorType = $this->categorizeError($exception);
            
            // 統計
            $stats['by_error'][$errorType] = ($stats['by_error'][$errorType] ?? 0) + 1;
            $stats['by_episode'][$episodeId] = ($stats['by_episode'][$episodeId] ?? 0) + 1;
            
            $rows[] = [
                $failure->id,
                $episodeId ?: 'N/A',
                $errorType,
                $failure->failed_at,
                $payload['attempts'] ?? 0
            ];
        }

        $this->table($headers, $rows);
        
        // 顯示統計
        $this->newLine();
        $this->info('📊 失敗統計：');
        $this->line("總失敗數：{$stats['total']}");
        
        $this->newLine();
        $this->info('按錯誤類型分組：');
        foreach ($stats['by_error'] as $type => $count) {
            $this->line("  • {$type}: {$count} 次");
        }
        
        $this->newLine();
        $this->info('失敗次數最多的影片：');
        arsort($stats['by_episode']);
        $topEpisodes = array_slice($stats['by_episode'], 0, 5, true);
        foreach ($topEpisodes as $episodeId => $count) {
            if ($episodeId) {
                $episode = DramaEpisode::find($episodeId);
                $title = $episode ? "Episode {$episode->seq}" : "Episode {$episodeId}";
                $this->line("  • {$title}: {$count} 次失敗");
            }
        }

        return 0;
    }

    /**
     * 重試失敗的任務
     */
    protected function retryFailures()
    {
        $failures = DB::table('failed_jobs')
            ->where('payload', 'like', '%GenerateVideoThumbnail%')
            ->orderBy('failed_at', 'desc')
            ->get();

        if ($failures->isEmpty()) {
            $this->info('沒有失敗的任務需要重試');
            return 0;
        }

        $this->info("找到 {$failures->count()} 個失敗的任務");
        
        if (!$this->confirm('確定要重試這些任務嗎？')) {
            return 0;
        }

        $retried = 0;
        $skipped = 0;

        foreach ($failures as $failure) {
            $payload = json_decode($failure->payload, true);
            $episodeId = $this->extractEpisodeId($payload);
            
            if (!$episodeId) {
                $skipped++;
                continue;
            }

            // 檢查影片是否還存在
            $episode = DramaEpisode::find($episodeId);
            if (!$episode) {
                $this->warn("Episode {$episodeId} 不存在，跳過");
                $skipped++;
                continue;
            }

            // 重新派發 Job
            GenerateVideoThumbnail::dispatch($episodeId, true)
                ->onQueue('thumbnails')
                ->delay(now()->addSeconds($retried * 5));
            
            // 刪除失敗記錄
            DB::table('failed_jobs')->where('id', $failure->id)->delete();
            
            $retried++;
            $this->line("重新排程 Episode {$episodeId} 的縮圖生成");
        }

        $this->newLine();
        $this->info("✅ 重試 {$retried} 個任務");
        if ($skipped > 0) {
            $this->warn("⚠️ 跳過 {$skipped} 個無效任務");
        }

        return 0;
    }

    /**
     * 清除失敗記錄
     */
    protected function clearFailures()
    {
        $count = DB::table('failed_jobs')
            ->where('payload', 'like', '%GenerateVideoThumbnail%')
            ->count();

        if ($count === 0) {
            $this->info('沒有失敗記錄需要清除');
            return 0;
        }

        if (!$this->confirm("確定要清除 {$count} 筆失敗記錄嗎？")) {
            return 0;
        }

        DB::table('failed_jobs')
            ->where('payload', 'like', '%GenerateVideoThumbnail%')
            ->delete();

        $this->info("已清除 {$count} 筆失敗記錄");
        return 0;
    }

    /**
     * 從 payload 中提取 Episode ID
     */
    protected function extractEpisodeId(array $payload): ?int
    {
        // 解析 Job 資料
        $data = unserialize($payload['data']['command'] ?? '');
        
        if ($data && property_exists($data, 'episodeId')) {
            return $data->episodeId;
        }
        
        return null;
    }

    /**
     * 分類錯誤類型
     */
    protected function categorizeError(array $exception): string
    {
        $message = $exception['message'] ?? 'Unknown error';
        
        if (str_contains($message, 'FFMpeg')) {
            return 'FFMpeg 錯誤';
        }
        
        if (str_contains($message, 'YouTube')) {
            return 'YouTube 錯誤';
        }
        
        if (str_contains($message, 'timeout')) {
            return '超時錯誤';
        }
        
        if (str_contains($message, 'not found')) {
            return '檔案不存在';
        }
        
        if (str_contains($message, 'network') || str_contains($message, 'download')) {
            return '網路錯誤';
        }
        
        return '其他錯誤';
    }
}
