<?php

namespace App\Services;

use App\Repositories\NotificationRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;

class NotificationService extends BaseService
{
    /**
     * 建構子
     */
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
        parent::__construct($notificationRepository);
    }

    /**
     * 建立通知
     */
    public function createNotification($type, $recipientType, $recipientId, $title, $message, $data = null)
    {
        try {
            $notification = $this->notificationRepository->createNotification([
                'type' => $type,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);


            return $notification;

        } catch (\Exception $e) {
            Log::error('通知系統：建立通知失敗', [
                'type' => $type,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 建立全體管理員通知
     */
    public function createAdminNotification($type, $title, $message, $data = null, $adminId = null)
    {
        return $this->createNotification($type, 'admin', $adminId, $title, $message, $data);
    }


    /**
     * 建立客服表單通知（給全體管理員）
     */
    public function createCustomerServiceNotification($customerService)
    {
        $title = '新的客服訊息';
        $message = "來自 {$customerService->name} 的訊息：{$customerService->subject}";
        $data = [
            'customer_service_id' => $customerService->id,
            'customer_name' => $customerService->name,
            'customer_email' => $customerService->email,
            'subject' => $customerService->subject,
            'action_url' => route('admin.customer-services'),
        ];

        return $this->createAdminNotification('customer_service', $title, $message, $data);
    }

    /**
     * 取得管理員通知列表
     */
    public function getAdminNotifications($adminId = null, $limit = 10, $onlyUnread = false)
    {
        return $this->notificationRepository->getNotifications('admin', $adminId, $limit, $onlyUnread);
    }


    /**
     * 取得管理員未讀通知數量
     */
    public function getAdminUnreadCount($adminId = null)
    {
        return $this->notificationRepository->getUnreadCount('admin', $adminId);
    }


    /**
     * 標記通知為已讀
     */
    public function markAsRead($id)
    {
        try {
            $result = $this->notificationRepository->markAsRead($id);

            if ($result > 0) {
                return $this->ReturnHandle(true, '已標記為已讀');
            } else {
                return $this->ReturnHandle(false, '通知不存在或已讀');
            }

        } catch (\Exception $e) {
            Log::error('通知系統：標記已讀失敗', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->ReturnHandle(false, '標記失敗，請稍後再試');
        }
    }

    /**
     * 標記全部通知為已讀
     */
    public function markAllAsRead($recipientType, $recipientId = null)
    {
        try {
            $result = $this->notificationRepository->markAllAsRead($recipientType, $recipientId);

            return $this->ReturnHandle(true, "已標記 {$result} 筆通知為已讀");

        } catch (\Exception $e) {
            Log::error('通知系統：標記全部已讀失敗', [
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return $this->ReturnHandle(false, '標記失敗，請稍後再試');
        }
    }

    /**
     * 標記管理員所有通知為已讀
     */
    public function markAllAdminAsRead($adminId = null)
    {
        return $this->markAllAsRead('admin', $adminId);
    }


    /**
     * 取得分頁通知列表（用於管理頁面）
     */
    public function getPaginatedNotifications($recipientType, $recipientId = null, $perPage = 15, $filters = [])
    {
        return $this->notificationRepository->getPaginatedNotifications($recipientType, $recipientId, $perPage, $filters);
    }

    /**
     * 清理舊通知
     */
    public function cleanOldNotifications($recipientType, $recipientId = null, $keepCount = 100)
    {
        try {
            $deleted = $this->notificationRepository->cleanOldNotifications($recipientType, $recipientId, $keepCount);


            return $deleted;

        } catch (\Exception $e) {
            Log::error('通知系統：清理舊通知失敗', [
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 取得通知統計資料
     */
    public function getNotificationStats($recipientType, $recipientId = null)
    {
        return $this->notificationRepository->getNotificationStats($recipientType, $recipientId);
    }

    /**
     * 格式化通知資料供前端使用
     */
    public function formatNotificationsForFrontend($notifications)
    {
        return $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'icon_class' => $notification->icon_class,
                'formatted_time' => $notification->formatted_time,
                'is_read' => $notification->is_read,
                'data' => $notification->data,
                'created_at' => $notification->created_at->toISOString(),
            ];
        });
    }

}