<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CategoryAggregationService;

class CategoryAggregation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demographics:aggregate-category
                            {date? : 指定要處理的日期 (YYYY-MM-DD)，預設為昨天}
                            {--recalculate : 全量重算模式：清空表格後重新計算所有週期統計}
                            {--weekly : 週統計更新模式：僅更新週統計資料}
                            {--monthly : 月統計更新模式：僅更新月統計資料}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '分類統計聚合（從 view_demographics → category_aggregations）
                              預設：增量更新（處理昨天的資料，更新 4 種週期）
                              --recalculate：全量重算（週日執行，重新計算全部週期統計）
                              --weekly：週統計更新（週一執行，更新上週統計）
                              --monthly：月統計更新（月初執行，更新上月統計）';

    protected $service;

    /**
     * Create a new command instance.
     */
    public function __construct(CategoryAggregationService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);

        $date = $this->argument('date');
        $recalculate = $this->option('recalculate');
        $weekly = $this->option('weekly');
        $monthly = $this->option('monthly');

        $this->info('開始分類統計聚合...');

        // 全量重算模式
        if ($recalculate) {
            $this->warn('⚠️  全量重算模式');
            $this->info('這將清空 category_aggregations 表格並重新計算所有週期統計');

            $result = $this->service->recalculateAllCategoryStats();

            if ($result['success']) {
                $this->info('✅ ' . $result['message']);

                if (isset($result['data'])) {
                    $executionTime = round(microtime(true) - $startTime, 2);
                    $this->table(
                        ['項目', '數值'],
                        [
                            ['總內容類型', $result['data']['total_content_types'] ?? 0],
                            ['總分類數', $result['data']['total_categories'] ?? 0],
                            ['總記錄數', $result['data']['total_records'] ?? 0],
                            ['執行時間', $executionTime . ' 秒'],
                        ]
                    );
                }

                return 0;
            } else {
                $this->error('❌ ' . $result['message']);
                return 1;
            }
        }

        // 週統計更新模式
        if ($weekly) {
            $this->info('📊 週統計更新模式');
            // TODO: 實作週統計更新邏輯（目前暫時執行一般聚合）
            $result = $this->service->aggregateCategoryStats($date);
        }
        // 月統計更新模式
        elseif ($monthly) {
            $this->info('📊 月統計更新模式');
            // TODO: 實作月統計更新邏輯（目前暫時執行一般聚合）
            $result = $this->service->aggregateCategoryStats($date);
        }
        // 增量更新模式（預設）
        else {
            if ($date) {
                $this->info("指定日期：{$date}");
            } else {
                $this->info('日期：昨天（預設）');
            }

            $result = $this->service->aggregateCategoryStats($date);
        }

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);

            if (isset($result['data'])) {
                foreach ($result['data'] as $contentType => $periods) {
                    $this->info("\n內容類型：{$contentType}");

                    foreach ($periods as $periodType => $periodResult) {
                        $this->line("  - {$periodType}：{$periodResult['total_categories']} 個分類，新增 {$periodResult['inserted']} 筆，更新 {$periodResult['updated']} 筆");
                    }
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->line("\n執行時間：{$executionTime} 秒");

            return 0;
        } else {
            $this->error('❌ ' . $result['message']);
            return 1;
        }
    }
}
