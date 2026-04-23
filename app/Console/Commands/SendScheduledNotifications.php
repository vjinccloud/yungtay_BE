<?php

namespace App\Console\Commands;

use App\Services\MemberNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledNotifications extends Command
{
    /**
     * 指令名稱
     *
     * @var string
     */
    protected $signature = 'notifications:send-scheduled
                            {--dry-run : 顯示將要發送的通知但不實際發送}
                            {--detail : 顯示詳細輸出}';

    /**
     * 指令描述
     *
     * @var string
     */
    protected $description = '發送已排程的會員通知';

    /**
     * 通知服務
     *
     * @var MemberNotificationService
     */
    private MemberNotificationService $notificationService;

    /**
     * 建立新的指令實例
     */
    public function __construct(MemberNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * 執行指令
     *
     * @return int
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        try {
            $result = $this->notificationService->processScheduledNotifications();

            if ($result['status']) {
                $data = $result['data'] ?? [];

                // 如果沒有通知需要處理，靜默結束
                if (!empty($data['is_empty'])) {
                    return Command::SUCCESS;
                }

                $executionTime = round(microtime(true) - $startTime, 2);

                // 記錄開始 - 僅在有通知處理時
                Log::channel('member-notifications')->info('==================== 排程通知處理開始 ====================');
                Log::channel('member-notifications')->info('執行時間: ' . now()->format('Y-m-d H:i:s'));

                // 顯示中文統計結果
                $this->info($result['msg']);

                // 顯示成功發送的通知主旨
                if (!empty($data['processed_notifications'])) {
                    $this->info("\n📧 成功發送的通知:");
                    foreach ($data['processed_notifications'] as $notification) {
                        $this->info("   • {$notification['title']} (發送 {$notification['member_count']} 位會員)");
                    }
                }

                // 顯示失敗發送的通知主旨
                if (!empty($data['failed_notifications'])) {
                    $this->error("\n❌ 發送失敗的通知:");
                    foreach ($data['failed_notifications'] as $notification) {
                        $this->error("   • {$notification['title']} (影響 {$notification['member_count']} 位會員)");
                    }
                    $this->warn("請檢查詳細錯誤 log：storage/logs/member-notifications/");
                }

                $this->info("\n⏱️  執行時間: {$executionTime} 秒");

                // 詳細記錄到專用 log
                Log::channel('member-notifications')->info("========================================");
                Log::channel('member-notifications')->info("排程通知發送完成");
                Log::channel('member-notifications')->info("成功發送會員數: " . ($data['processed_members'] ?? 0));
                Log::channel('member-notifications')->info("發送失敗會員數: " . ($data['failed_members'] ?? 0));
                Log::channel('member-notifications')->info("處理通知數: " . ($data['total_notifications'] ?? 0));

                // 記錄成功發送的通知主旨
                if (!empty($data['processed_notifications'])) {
                    Log::channel('member-notifications')->info("成功發送通知清單:");
                    foreach ($data['processed_notifications'] as $notification) {
                        Log::channel('member-notifications')->info("  • {$notification['title']} (發送 {$notification['member_count']} 位會員)");
                    }
                }

                // 記錄失敗發送的通知主旨
                if (!empty($data['failed_notifications'])) {
                    Log::channel('member-notifications')->info("發送失敗通知清單:");
                    foreach ($data['failed_notifications'] as $notification) {
                        Log::channel('member-notifications')->info("  • {$notification['title']} (影響 {$notification['member_count']} 位會員) - 錯誤: {$notification['error']}");
                    }
                }

                Log::channel('member-notifications')->info("執行時間: {$executionTime} 秒");
                Log::channel('member-notifications')->info("========================================");

                return Command::SUCCESS;
            } else {
                $this->error('處理排程通知失敗：' . $result['msg']);
                Log::channel('member-notifications')->error('排程通知處理失敗', ['error' => $result['msg']]);
                Log::channel('member-notifications')->info('==================== 排程通知處理完成 (失敗) ====================');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('執行排程通知指令時發生錯誤：' . $e->getMessage());
            Log::channel('member-notifications')->error('排程通知指令執行錯誤', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            Log::channel('member-notifications')->info('==================== 排程通知處理完成 (錯誤) ====================');
            return Command::FAILURE;
        }
    }
}