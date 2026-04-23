<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MemberNotificationService;
use App\Models\User;
use App\Http\Requests\Admin\MemberNotificationRequest;
use App\Enums\SendMode;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberNotificationController extends Controller
{
    private MemberNotificationService $service;

    public function __construct(MemberNotificationService $service)
    {
        $this->service = $service;
    }

    /**
     * 顯示通知列表頁面
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'target_type']);
        $sortColumn = $request->input('sortColumn') ?? 'created_at'; // 默認排序列為 created_at
        $sortDirection = $request->input('sortDirection') ?? 'desc'; // 默認排序方向為降序
        $perPage = $request->input('length') ?? 10;

        $notifications = $this->service->paginate($perPage, $sortColumn, $sortDirection, $filters);

        return Inertia::render('Admin/MemberNotification/Index', compact('notifications'));
    }

    /**
     * 顯示新增表單
     */
    public function create()
    {
        return Inertia::render('Admin/MemberNotification/Form', [
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get()
        ]);
    }

    /**
     * 儲存新通知
     */
    public function store(MemberNotificationRequest $request)
    {
        // 轉換前端參數格式以符合 Service 層期望
        $data = $request->validated();

        // 轉換 send_type 為 send_mode（前端用 now/scheduled，後端用 immediate/scheduled）
        $data['send_mode'] = $data['send_type'] === 'now' ? SendMode::IMMEDIATE->value : SendMode::SCHEDULED->value;

        $result = $this->service->createNotification($data);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
     * 顯示通知詳情
     */
    public function show(Request $request, $id)
    {
        $notification = $this->service->getFormattedNotification($id);

        if (!$notification) {
            return redirect()->route('admin.member-notifications')
                ->with('result', ['status' => false, 'msg' => '通知不存在']);
        }

        return Inertia::render('Admin/MemberNotification/Show', compact('notification'));
    }

    /**
     * AJAX 端點：取得通知接收者列表
     */
    public function recipients(Request $request, $id)
    {
        $notification = $this->service->find($id);

        if (!$notification) {
            return response()->json([
                'data' => [],
                'total' => 0
            ], 404);
        }

        // 當 ordering: false 時，sortColumn 會是 null，使用預設值
        $sortColumn = $request->input('sortColumn') ?: 'sent_at';
        $sortDirection = $request->input('sortDirection') ?: 'desc';
        $perPage = $request->input('length') ?? 10;

        $recipients = $this->service->getNotificationRecipients($id, $perPage, $sortColumn, $sortDirection);

        return response()->json([
            'data' => $recipients->items(),
            'total' => $recipients->total()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $result = $this->service->deleteNotification($id);

        return redirect()
            ->back()
            ->with('result', $result);
    }
}
