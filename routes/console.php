<?php

use Illuminate\Support\Facades\Schedule;

// 觀看數統計同步任務
// 每 5 分鐘執行一次，與首頁快取時間同步
Schedule::command('views:sync')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// CNA RSS 新聞同步任務
// 從 config/cna.php 讀取同步間隔設定
$syncInterval = config('cna.sync.interval', 10); // 預設 10 分鐘

Schedule::command('cna:sync')
    ->cron("*/{$syncInterval} * * * *")  // 使用 config 設定的間隔
    ->withoutOverlapping()  // 防止重疊執行
    ->runInBackground();    // 背景執行

// Laravel 的 daily driver 會自動清理超過設定天數的日誌
// config/logging.php 中已設定 'days' => 30

// 會員通知排程發送任務
// 每5分鐘檢查一次是否有需要發送的排程通知
Schedule::command('notifications:send-scheduled')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// 清理孤立的觀看紀錄和收藏紀錄，並重新計算統計資料
// 每天凌晨 2 點執行，清理不存在對應內容的孤立紀錄後重新計算觀看統計
Schedule::call(function () {
    try {
        // 1. 先執行清理孤立記錄
        \Artisan::call('cleanup:orphaned-records', ['--no-interaction' => true]);
        \Log::info('Orphaned records cleanup completed');

        // 2. 再執行完整重新計算（加上 --no-interaction 跳過確認提示）
        $exitCode = \Artisan::call('views:sync', ['--recalculate' => true, '--no-interaction' => true]);
        $output = \Artisan::output();

        if ($exitCode === 0) {
            \Log::info('View statistics recalculation completed', [
                'output' => $output
            ]);
        } else {
            \Log::error('View statistics recalculation failed', [
                'exit_code' => $exitCode,
                'output' => $output
            ]);
        }
    } catch (\Exception $e) {
        \Log::error('Daily cleanup and recalculate failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})
    ->dailyAt('02:00')
    ->name('daily-cleanup-and-recalculate')
    ->withoutOverlapping();

// 每日人口統計聚合任務 - 平日增量更新（週一到週六）
// 每天凌晨 3 點執行，從 view_logs 聚合昨天的資料到 view_demographics
Schedule::command('demographics:aggregate-daily')
    ->dailyAt('03:00')
    ->when(function () {
        return !now()->isSunday(); // 週日除外
    })
    ->withoutOverlapping()
    ->runInBackground();

// 每日人口統計聚合任務 - 週日全量重算
// 週日凌晨 3 點執行，清空表格後重新計算所有歷史資料
Schedule::command('demographics:aggregate-daily --recalculate')
    ->weeklyOn(0, '03:00')  // 週日凌晨 3:00
    ->withoutOverlapping()
    ->runInBackground();

// 分類統計聚合任務 - 平日增量更新（週一到週六）
// 每天凌晨 4 點執行，從 view_demographics 聚合昨天的資料到 category_aggregations
// 只更新 daily 和 all_time 週期
Schedule::command('demographics:aggregate-category')
    ->dailyAt('04:00')
    ->when(function () {
        return !now()->isSunday(); // 週日除外
    })
    ->withoutOverlapping()
    ->runInBackground();

// 分類統計聚合任務 - 週一週統計更新
// 週一凌晨 4:30 執行，聚合上週的週統計資料
Schedule::command('demographics:aggregate-category --weekly')
    ->weeklyOn(1, '04:30')  // 週一凌晨 4:30
    ->withoutOverlapping()
    ->runInBackground();

// 分類統計聚合任務 - 月初月統計更新
// 每月 1 日凌晨 4:30 執行，聚合上月的月統計資料
Schedule::command('demographics:aggregate-category --monthly')
    ->monthlyOn(1, '04:30')  // 每月 1 日凌晨 4:30
    ->withoutOverlapping()
    ->runInBackground();

// 分類統計聚合任務 - 週日全量重算
// 週日凌晨 4 點執行，清空表格後重新計算所有週期統計
Schedule::command('demographics:aggregate-category --recalculate')
    ->weeklyOn(0, '04:00')  // 週日凌晨 4:00
    ->withoutOverlapping()
    ->runInBackground();

// Sitemap 自動更新任務
// 每天凌晨 5 點執行，生成靜態 Sitemap XML 檔案
// 支援分語系（zh/en）、分檔（每 10,000 筆一個檔案）
Schedule::command('sitemap:generate')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->runInBackground();

// ==========================================
// ECPay 綠界發票自動開立任務
// ==========================================

// ECPay 發票自動開立任務
// 每 5 分鐘執行一次，自動為已付款成功但尚未開立發票的訂單開立電子發票
// 每次最多處理 50 筆，避免 API 請求過於頻繁
Schedule::command('ecpay:issue-invoices --limit=50')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
