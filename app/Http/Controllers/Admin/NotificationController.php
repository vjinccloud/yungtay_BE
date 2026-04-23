<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * 取得管理員通知列表（API 格式）
     */
    public function index(Request $request)
    {
        $adminId = auth('admin')->id();
        $limit = $request->get('limit', 10);
        $onlyUnread = $request->boolean('only_unread', false);

        $notifications = $this->notificationService->getAdminNotifications($adminId, $limit, $onlyUnread);
        $formattedNotifications = $this->notificationService->formatNotificationsForFrontend($notifications);

        return response()->json([
            'success' => true,
            'notifications' => $formattedNotifications,
            'unread_count' => $this->notificationService->getAdminUnreadCount($adminId)
        ]);
    }

    /**
     * 取得未讀通知數量
     */
    public function unreadCount()
    {
        $adminId = auth('admin')->id();
        $count = $this->notificationService->getAdminUnreadCount($adminId);

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * 標記單一通知為已讀
     */
    public function markAsRead($id)
    {
        $result = $this->notificationService->markAsRead($id);

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /**
     * 標記全部通知為已讀
     */
    public function markAllAsRead()
    {
        $adminId = auth('admin')->id();
        $result = $this->notificationService->markAllAdminAsRead($adminId);

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /**
     * 通知管理頁面（如需要的話）
     */
    public function manage(Request $request)
    {
        $adminId = auth('admin')->id();
        $perPage = $request->get('per_page', 15);

        $filters = [
            'type' => $request->get('type'),
            'status' => $request->get('status'),
        ];

        $notifications = $this->notificationService->getPaginatedNotifications('admin', $adminId, $perPage, $filters);
        $stats = $this->notificationService->getNotificationStats('admin', $adminId);

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'filters' => $filters
        ]);
    }
}