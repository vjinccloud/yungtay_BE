<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\BaseRepository;

class NotificationRepository extends BaseRepository
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
    }

    /**
     * 取得指定接收者的未讀通知數量
     */
    public function getUnreadCount($recipientType, $recipientId = null)
    {
        return $this->model->byRecipient($recipientType, $recipientId)
                          ->unread()
                          ->count();
    }

    /**
     * 取得指定接收者的通知列表
     */
    public function getNotifications($recipientType, $recipientId = null, $limit = 10, $onlyUnread = false)
    {
        $query = $this->model->byRecipient($recipientType, $recipientId);

        if ($onlyUnread) {
            $query->unread();
        }

        return $query->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * 取得分頁通知列表
     */
    public function getPaginatedNotifications($recipientType, $recipientId = null, $perPage = 15, $filters = [])
    {
        $query = $this->model->byRecipient($recipientType, $recipientId);

        // 篩選條件
        if (isset($filters['type']) && $filters['type']) {
            $query->byType($filters['type']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'unread') {
                $query->unread();
            } elseif ($filters['status'] === 'read') {
                $query->read();
            }
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * 標記指定通知為已讀
     */
    public function markAsRead($id)
    {
        return $this->model->where('id', $id)
                          ->whereNull('read_at')
                          ->update(['read_at' => now()]);
    }

    /**
     * 標記指定接收者的所有通知為已讀
     */
    public function markAllAsRead($recipientType, $recipientId = null)
    {
        return $this->model->byRecipient($recipientType, $recipientId)
                          ->unread()
                          ->update(['read_at' => now()]);
    }

    /**
     * 建立新通知
     */
    public function createNotification($data)
    {
        return $this->model->create([
            'type' => $data['type'],
            'recipient_type' => $data['recipient_type'],
            'recipient_id' => $data['recipient_id'] ?? null,
            'title' => $data['title'],
            'message' => $data['message'],
            'data' => $data['data'] ?? null,
        ]);
    }

    /**
     * 批量建立通知（例如：給全體管理員）
     */
    public function createBulkNotifications($type, $recipientType, $recipientIds, $title, $message, $data = null)
    {
        $notifications = [];
        $now = now();

        foreach ($recipientIds as $recipientId) {
            $notifications[] = [
                'type' => $type,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $this->model->insert($notifications);
    }

    /**
     * 建立全體通知（recipient_id = null）
     */
    public function createGlobalNotification($type, $recipientType, $title, $message, $data = null)
    {
        return $this->createNotification([
            'type' => $type,
            'recipient_type' => $recipientType,
            'recipient_id' => null, // null 代表全體
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 刪除舊通知（保留最新 N 筆）
     */
    public function cleanOldNotifications($recipientType, $recipientId = null, $keepCount = 100)
    {
        $query = $this->model->byRecipient($recipientType, $recipientId);

        $totalCount = $query->count();

        if ($totalCount > $keepCount) {
            $oldestIds = $this->model->byRecipient($recipientType, $recipientId)
                                   ->orderBy('created_at', 'desc')
                                   ->skip($keepCount)
                                   ->pluck('id');

            return $this->model->whereIn('id', $oldestIds)->delete();
        }

        return 0;
    }

    /**
     * 取得通知統計資料
     */
    public function getNotificationStats($recipientType, $recipientId = null)
    {
        $baseQuery = $this->model->byRecipient($recipientType, $recipientId);

        return [
            'total' => $baseQuery->count(),
            'unread' => $baseQuery->clone()->unread()->count(),
            'today' => $baseQuery->clone()->whereDate('created_at', today())->count(),
            'this_week' => $baseQuery->clone()->where('created_at', '>=', now()->startOfWeek())->count(),
        ];
    }

}