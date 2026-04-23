<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ViewDemographicService;

class DailyDemographicsAggregation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demographics:aggregate-daily
                            {date? : 指定要處理的日期 (YYYY-MM-DD)，預設為昨天}
                            {--recalculate : 全量重算模式：清空表格後重新計算所有歷史資料}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日人口統計聚合（從 view_logs → view_demographics）
                              預設：增量更新（處理昨天的資料）
                              --recalculate：全量重算（週日執行，重新計算全部歷史資料）';

    protected $service;

    /**
     * Create a new command instance.
     */
    public function __construct(ViewDemographicService $service)
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

        $this->info('開始每日人口統計聚合...');

        // 全量重算模式
        if ($recalculate) {
            $this->warn('⚠️  全量重算模式');
            $this->info('這將清空 view_demographics 表格並重新計算所有歷史資料');

            $result = $this->service->recalculateAllDemographics();

            if ($result['success']) {
                $this->info('✅ ' . $result['message']);

                if (isset($result['data'])) {
                    $executionTime = round(microtime(true) - $startTime, 2);
                    $this->table(
                        ['項目', '數值'],
                        [
                            ['總處理日期數', $result['data']['total_dates'] ?? 0],
                            ['總群組數', $result['data']['total_groups'] ?? 0],
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

        // 增量更新模式（預設）
        if ($date) {
            $this->info("指定日期：{$date}");
        } else {
            $this->info('日期：昨天（預設）');
        }

        $result = $this->service->aggregateDailyDemographics($date);

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);

            if (isset($result['data'])) {
                $executionTime = round(microtime(true) - $startTime, 2);
                $this->table(
                    ['項目', '數值'],
                    [
                        ['日期', $result['data']['date']],
                        ['處理群組數', $result['data']['total_groups']],
                        ['新增記錄', $result['data']['inserted']],
                        ['更新記錄', $result['data']['updated']],
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
}
