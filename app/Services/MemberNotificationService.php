<?php

namespace App\Services;

use App\Repositories\MemberNotificationRepository;
use App\Enums\NotificationStatus;
use App\Enums\TargetType;
use App\Enums\SendMode;
use App\Enums\RecipientSentStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class MemberNotificationService extends BaseService
{
    protected MemberNotificationRepository $notificationRepository;

    public function __construct(
        MemberNotificationRepository $notificationRepository
    ) {
        $this->notificationRepository = $notificationRepository;
        parent::__construct($notificationRepository);
    }

   

    /**
     * 建立會員通知
     *
     * @param array $data 通知資料
     * @return array
     */
    public function createNotification(array $data): array
    {
        try {
            // 資料驗證和處理
            $notificationData = $this->prepareNotificationData($data);

            // 取出目標用戶ID（如果有）
            $userIds = $data['target_user_ids'] ?? [];

            // 使用事務處理通知建立和接收者記錄
            $notification = DB::transaction(function () use ($notificationData, $userIds) {
                // 使用 BaseRepository 的 save 方法（會觸發 Observer）
                $notification = $this->notificationRepository->save($notificationData);
                $this->notificationRepository->addRecipients($notification, $userIds);
                return $notification;
            });

            // 根據發送模式處理
            if ($data['send_mode'] === SendMode::IMMEDIATE->value) {
                // 立即發送
                $result = $this->sendImmediately($notification);
                if (!$result['status']) {
                    return $result;
                }
            }

            return $this->ReturnHandle(true, '通知建立成功', route('admin.member-notifications'), [
                'notification' => $notification->load('recipients')
            ]);

        } catch (Exception $e) {
            Log::error('建立會員通知失敗', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return $this->ReturnHandle(false, '建立通知失敗：' . $e->getMessage());
        }
    }


    /**
     * 立即發送通知
     *
     * @param mixed $notification
     * @return array
     */
    public function sendImmediately($notification): array
    {
        try {
            // 檢查是否有接收者
            $recipientCount = $notification->recipients()->count();

            if ($recipientCount === 0) {
                return $this->ReturnHandle(false, '沒有符合條件的接收者');
            }

            // 這裡可以整合實際的通知發送邏輯
            // 例如：推播通知、Email、簡訊等
            // 目前先標記為已發送

            // 使用 Repository 批次更新狀態
            $this->notificationRepository->markNotificationStatus($notification, NotificationStatus::SENT);

            Log::info('會員通知發送成功', [
                'notification_id' => $notification->id,
                'recipient_count' => $recipientCount,
                'title' => $notification->getTranslation('title', 'zh_TW')
            ]);

            return $this->ReturnHandle(true, "通知已發送給 {$recipientCount} 位會員");

        } catch (Exception $e) {
            Log::error('發送會員通知失敗', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);

            return $this->ReturnHandle(false, '發送通知失敗：' . $e->getMessage());
        }
    }

    /**
     * 取得格式化的通知資料
     *
     * @param int $id
     * @return array|null
     */
    public function getFormattedNotification(int $id): ?array
    {
        return $this->notificationRepository->findFormatted($id);
    }

    /**
     * 處理排程通知發送
     *
     * @return array
     */
    public function processScheduledNotifications(): array
    {
        try {
            $notifications = $this->notificationRepository->getReadyToSendNotifications();

            if ($notifications->isEmpty()) {
                // 沒有通知時靜默返回，不記錄日誌
                return $this->ReturnHandle(true, '沒有需要發送的排程通知', null, [
                    'processed_members' => 0,
                    'failed_members' => 0,
                    'total_members' => 0,
                    'processed_notifications' => [],
                    'failed_notifications' => [],
                    'total_notifications' => 0,
                    'is_empty' => true // 標記為空結果
                ]);
            }

            $processedMemberCount = 0;
            $failedMemberCount = 0;
            $processedNotifications = [];
            $failedNotifications = [];

            foreach ($notifications as $notification) {
                $title = $notification->getTranslation('title', 'zh_TW');
                $recipientCount = $notification->recipients()->count();

                $result = $this->sendImmediately($notification);

                if ($result['status']) {
                    $processedMemberCount += $recipientCount;
                    $processedNotifications[] = [
                        'id' => $notification->id,
                        'title' => $title,
                        'member_count' => $recipientCount
                    ];
                    // 成功只記錄簡單信息
                    Log::channel('member-notifications')->info("通知發送成功: {$title} (發送 {$recipientCount} 位會員)");
                } else {
                    $failedMemberCount += $recipientCount;
                    $failedNotifications[] = [
                        'id' => $notification->id,
                        'title' => $title,
                        'member_count' => $recipientCount,
                        'error' => $result['msg']
                    ];
                    // 失敗才記錄詳細 JSON 資料
                    Log::channel('member-notifications')->error('通知發送失敗', [
                        'notification_id' => $notification->id,
                        'title' => $title,
                        'recipient_count' => $recipientCount,
                        'error' => $result['msg']
                    ]);
                }
            }

            return $this->ReturnHandle(true, sprintf('排程通知發送完成：成功發送 %d 位會員，發送失敗 %d 位會員', $processedMemberCount, $failedMemberCount), null, [
                'processed_members' => $processedMemberCount,
                'failed_members' => $failedMemberCount,
                'total_members' => $processedMemberCount + $failedMemberCount,
                'processed_notifications' => $processedNotifications,
                'failed_notifications' => $failedNotifications,
                'total_notifications' => $notifications->count()
            ]);

        } catch (Exception $e) {
            Log::channel('member-notifications')->error('處理排程通知失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->ReturnHandle(false, '處理排程通知失敗：' . $e->getMessage());
        }
    }


    /**
     * 取得單一通知
     *
     * @param mixed $id 通知ID
     * @return mixed
     */
    public function find($id)
    {
        return $this->notificationRepository->find($id);
    }

    /**
     * 取得通知的接收者列表（分頁）
     *
     * @param int $notificationId 通知ID
     * @param int $perPage 每頁數量
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @return LengthAwarePaginator
     */
    public function getNotificationRecipients(int $notificationId, int $perPage = 15, string $sortColumn = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator
    {
        return $this->notificationRepository->getNotificationRecipients($notificationId, $perPage, $sortColumn, $sortDirection);
    }

    /**
     * 刪除通知
     *
     * @param int $id 通知ID
     * @return array
     */
    public function deleteNotification(int $id): array
    {
        try {
            $notification = $this->notificationRepository->find($id);

            if (!$notification) {
                return $this->ReturnHandle(false, '通知不存在');
            }

            $this->notificationRepository->delete($id);

            return $this->ReturnHandle(true, '通知已刪除');

        } catch (Exception $e) {
            Log::error('刪除會員通知失敗', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->ReturnHandle(false, '刪除通知失敗：' . $e->getMessage());
        }
    }

    /**
     * 取得用戶通知列表
     *
     * @param int $userId 用戶ID
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return array
     */
    public function getUserNotifications(int $userId, array $filters = [], int $perPage = 15): array
    {
        try {
            // 調用 Repository 方法
            $result = $this->notificationRepository->getUserNotifications($userId, $filters, $perPage);

            return $this->ReturnHandle(true, '載入成功', null, $result);

        } catch (Exception $e) {
            Log::error('取得會員通知失敗', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->ReturnHandle(false, '載入失敗：' . $e->getMessage());
        }
    }

    /**
     * 取得用戶未讀通知數量
     *
     * @param int $userId 用戶ID
     * @return int
     */
    public function getUserUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUserUnreadCount($userId);
    }

    /**
     * 標記通知為已讀（會員前台使用）
     *
     * @param int $notificationId 通知ID (member_notification_recipients 表的 member_notification_id)
     * @return array
     */
    public function markAsRead(int $notificationId): array
    {
        try {
            $result = $this->notificationRepository->markSingleAsRead($notificationId, auth()->id());

            if ($result) {
                return $this->ReturnHandle(true, '已標記為已讀');
            } else {
                return $this->ReturnHandle(false, '通知不存在或已讀');
            }

        } catch (Exception $e) {
            Log::error('標記通知已讀失敗', [
                'notification_id' => $notificationId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->ReturnHandle(false, '標記已讀失敗：' . $e->getMessage());
        }
    }

    /**
     * 準備通知資料
     *
     * @param array $data 原始資料
     * @return array 處理後的資料
     */
    private function prepareNotificationData(array $data): array
    {
        $sendMode = SendMode::from($data['send_mode'] ?? SendMode::IMMEDIATE->value);

        $notificationData = [
            'title' => $data['title'], // 多語系陣列直接傳入
            'message' => $data['message'], // 多語系陣列直接傳入
            'target_type' => TargetType::from($data['target_type']),
            'send_mode' => $sendMode,
            'status' => $sendMode->getInitialStatus(), // 根據發送模式設定初始狀態
        ];

        // 處理排程時間
        if (!empty($data['scheduled_at'])) {
            $notificationData['scheduled_at'] = $data['scheduled_at'];
        }

        return $notificationData;
    }
}