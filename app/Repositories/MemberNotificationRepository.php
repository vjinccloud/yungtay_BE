<?php

namespace App\Repositories;

use App\Models\MemberNotification;
use App\Models\MemberNotificationRecipient;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Enums\NotificationStatus;
use App\Enums\TargetType;
use App\Enums\SendMode;
use App\Enums\RecipientSentStatus;
use App\Enums\RecipientReadStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class MemberNotificationRepository extends BaseRepository
{
    protected MemberNotificationRecipient $recipientModel;

    /**
     * 建構函式
     */
    public function __construct(
        MemberNotification $memberNotification,
        MemberNotificationRecipient $recipientModel
    ) {
        parent::__construct($memberNotification);
        $this->recipientModel = $recipientModel;
    }


    /**
     * 後台分頁列表（參考 ArticleRepository 模式）
     *
     * @param int $perPage 每頁數量
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @param array $filters 篩選條件
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, $sortColumn = 'created_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->model->orderBy($sortColumn, $sortDirection)
                          ->filter($filters)
                          ->paginate($perPage)
                          ->withQueryString()
                          ->through(fn ($notification) => [
                              'id' => $notification->id,
                              'title' => $notification->getTranslation('title', 'zh_TW'),
                              'message' => $notification->getTranslation('message', 'zh_TW'),
                              'target_type' => $notification->target_type,
                              'target_count' => $this->getTargetCount($notification),
                              'status' => $notification->status,
                              'send_mode' => $notification->send_mode,
                              'scheduled_at' => $notification->scheduled_at?->toDateTimeString(),
                              'sent_at' => $notification->sent_at?->toDateTimeString(),
                              'created_at' => $notification->created_at->toDateTimeString(),
                          ]);
    }

    /**
     * 建立會員通知並創建接收者記錄（含事務處理）
     *
     * @param array $data 通知資料
     * @param array $userIds 目標用戶ID陣列（target_type=specific時使用）
     * @return MemberNotification
     * @throws Exception
     */
    public function createNotification(array $data): MemberNotification
    {
        return $this->model->create($data);
    }

    public function addRecipients(MemberNotification $notification, array $userIds = []): void
    {
        $this->createRecipients($notification, $userIds);
    }

    /**
     * 標記通知狀態並批次更新接收者
     *
     * @param MemberNotification $notification
     * @param NotificationStatus $status
     * @param RecipientSentStatus $sentStatus
     * @return bool
     */
    public function markNotificationStatus(MemberNotification $notification, NotificationStatus $status, RecipientSentStatus $sentStatus = RecipientSentStatus::SENT): bool
    {
        DB::beginTransaction();

        try {
            // 更新主通知狀態
            $notification->update([
                'status' => $status,
                'sent_at' => now(),
            ]);

            // 批次更新接收者狀態
            $notification->recipients()->update([
                'sent_status' => $sentStatus,
                'sent_at' => now(),
            ]);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 取得用戶的通知列表（前台用）
     *
     * @param int $userId 用戶ID
     * @param array $filters 篩選條件
     * @param int $perPage 每頁數量
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->recipientModel->where('user_id', $userId)
                                         ->where('sent_status', RecipientSentStatus::SENT) // 只顯示已發送的通知
                                         ->with('notification') // 載入通知主體資料
                                         ->orderBy('created_at', 'desc')
                                         ->paginate($perPage)
                                         ->withQueryString()
                                         ->through(fn ($recipient) => [
                                             'id' => $recipient->member_notification_id,
                                             'title' => $recipient->notification->title ?? '',
                                             'message' => $recipient->notification->message ?? '',
                                             'is_read' => $recipient->read_status === RecipientReadStatus::READ, // 檢查 read_status 欄位
                                             'created_at' => $recipient->created_at->format('Y-m-d H:i:s'),
                                             'read_at' => $recipient->read_at?->format('Y-m-d H:i:s'),
                                         ]);
    }

    /**
     * 取得用戶未讀通知數量
     *
     * @param int $userId 用戶ID
     * @return int
     */
    public function getUserUnreadCount(int $userId): int
    {
        return $this->recipientModel->forUser($userId)
                                         ->unread()
                                         ->where('sent_status', RecipientSentStatus::SENT) // 只計算已發送的通知
                                         ->count();
    }

    /**
     * 標記用戶通知為已讀
     *
     * @param int $userId 用戶ID
     * @param array $notificationIds 通知ID陣列
     * @return int 受影響的行數
     */
    public function markAsReadByUser(int $userId, array $notificationIds = []): int
    {
        $query = $this->recipientModel->forUser($userId)
                                      ->unread()
                                      ->where('sent_status', RecipientSentStatus::SENT); // 只處理已發送的通知

        if (!empty($notificationIds)) {
            $query->whereIn('member_notification_id', $notificationIds);
        }

        return $query->update([
            'read_status' => RecipientReadStatus::READ,
            'read_at' => now(),
        ]);
    }

    /**
     * 標記單一通知為已讀
     *
     * @param int $notificationId 通知ID
     * @param int $userId 用戶ID
     * @return bool
     */
    public function markSingleAsRead(int $notificationId, int $userId): bool
    {
        $recipient = $this->recipientModel
            ->where('member_notification_id', $notificationId)
            ->where('user_id', $userId)
            ->where('read_status', RecipientReadStatus::UNREAD)
            ->where('sent_status', RecipientSentStatus::SENT) // 只處理已發送的通知
            ->first();

        if (!$recipient) {
            return false;
        }

        return $recipient->update([
            'read_status' => RecipientReadStatus::READ,
            'read_at' => now()
        ]);
    }

    /**
     * 取得通知的接收者列表（後台檢視用，支援分頁）
     *
     * @param int $notificationId 通知ID
     * @param int $perPage 每頁數量
     * @param string $sortColumn 排序欄位
     * @param string $sortDirection 排序方向
     * @return LengthAwarePaginator
     */
    public function getNotificationRecipients(int $notificationId, int $perPage = 15, string $sortColumn = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator
    {

        return $this->recipientModel->where('member_notification_id', $notificationId)
                                         ->with(['user', 'notification'])
                                         ->paginate($perPage)
                                         ->withQueryString()
                                         ->through(fn ($recipient) => [
                                             'id' => $recipient->id,
                                             'user_id' => $recipient->user_id,
                                             'user_name' => $recipient->user->name ?? 'N/A',
                                             'user_email' => $recipient->user->email ?? 'N/A',
                                             'user_created_at' => $recipient->user->created_at?->toDateString(),
                                             'sent_status' => $recipient->sent_status,
                                             'sent_status_label' => $recipient->sent_status->label(),
                                             'sent_at' => $recipient->sent_at?->toDateTimeString(),
                                             'notification_scheduled_at' => $recipient->notification->scheduled_at?->toDateTimeString(),
                                             'read_status' => $recipient->read_status,
                                             'read_status_label' => $recipient->read_status->label(),
                                             'read_at' => $recipient->read_at?->toDateTimeString(),
                                             'created_at' => $recipient->created_at->toDateTimeString(),
                                         ]);
    }

    /**
     * 取得格式化的通知資料（供顯示用）
     *
     * @param int $id
     * @return array|null
     */
    public function findFormatted(int $id): ?array
    {
        $notification = $this->find($id);

        if (!$notification) {
            return null;
        }

        return [
            'id' => $notification->id,
            'title' => [
                'zh_TW' => $notification->getTranslation('title', 'zh_TW'),
                'en' => $notification->getTranslation('title', 'en'),
            ],
            'message' => [
                'zh_TW' => $notification->getTranslation('message', 'zh_TW'),
                'en' => $notification->getTranslation('message', 'en'),
            ],
            'status' => $notification->status,
            'target_type' => $notification->target_type,
            'scheduled_at' => $notification->scheduled_at?->format('Y-m-d H:i:s'),
            'sent_at' => $notification->sent_at?->format('Y-m-d H:i:s'),
            'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $notification->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 取得準備發送的排程通知
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReadyToSendNotifications()
    {
        return $this->model->where('status', 'scheduled')
                          ->where('scheduled_at', '<=', now())
                          ->get();
    }

    /**
     * 計算目標用戶數量
     *
     * @param \App\Models\MemberNotification $notification
     * @return int
     */
    private function getTargetCount($notification): int
    {
        if ($notification->target_type === TargetType::ALL) {
            // 全部已驗證且啟用的會員（使用 scope 方法）
            return User::active()
                      ->emailVerified()
                      ->count();
        } else {
            // 特定會員（從 recipients 表計算）
            return $notification->recipients()->count();
        }
    }

    /**
     * 建立接收者記錄
     *
     * @param MemberNotification $notification
     * @param array $userIds
     * @return void
     * @throws Exception
     */
    private function createRecipients(MemberNotification $notification, array $userIds = []): void
    {
        try {
            if ($notification->target_type === TargetType::ALL) {
                $verifiedUserIds = User::active()->emailVerified()->pluck('id')->toArray();
            } elseif ($notification->target_type === TargetType::SPECIFIC && !empty($userIds)) {
                $verifiedUserIds = User::whereIn('id', $userIds)
                                    ->active()
                                    ->emailVerified()
                                    ->pluck('id')
                                    ->toArray();
            } else {
                return;
            }

            if (empty($verifiedUserIds)) {
                throw new Exception('沒有符合條件的會員（需已驗證且啟用狀態）');
            }

            // 一次批量插入
            $rows = array_map(fn($id) => [
                'member_notification_id' => $notification->id,
                'user_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ], $verifiedUserIds);

            $this->recipientModel::insert($rows);

        } catch (Exception $e) {
            \Log::error('建立通知接收者失敗', [
                'notification_id' => $notification->id,
                'target_type' => $notification->target_type->value,
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


}