<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerServiceRequest;
use App\Services\CustomerServiceService;
use App\Models\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerServiceController extends Controller
{
    public function __construct(
        private CustomerServiceService $customerServiceService,
    ) {
    }

    /**
     * 顯示客服信件列表頁面
     * 支援 DataTable + 分頁 + 搜尋篩選
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_replied', 'start_date', 'end_date']);
        $sortColumn = $request->input('sortColumn') ?? 'created_at';
        $sortDirection = $request->input('sortDirection') ?? 'desc';
        $perPage = $request->input('length') ?? '10';

        $customerServices = $this->customerServiceService->getCustomerServices($perPage, $sortColumn, $sortDirection, $filters);

        return Inertia::render('Admin/CustomerService/Index', [
            'customerServices' => $customerServices,
            'filters' => $filters
        ]);
    }

    /**
     * 顯示客服信件詳情頁面
     */
    public function show($id)
    {
        $customerService = $this->customerServiceService->find($id);

        if (!$customerService) {
            return redirect()->route('admin.customer-services')
                ->with('result', ['status' => false, 'msg' => '客服信件不存在']);
        }

        // 載入相關關聯資料
        $customerService->load('repliedBy');

        return Inertia::render('Admin/CustomerService/Show', [
            'customerService' => $customerService
        ]);
    }




    /**
     * 回覆客服信件
     */
    public function reply(Request $request, $id)
    {
        // 驗證回覆資料
        $validated = $request->validate([
            'reply_subject' => 'required|string|max:255',
            'reply_content' => 'required|string|max:2000',
            'admin_note' => 'nullable|string|max:1000',
        ], [
            'reply_subject.required' => '請輸入回覆主旨',
            'reply_subject.max' => '回覆主旨不可超過 255 個字',
            'reply_content.required' => '請輸入回覆內容',
            'reply_content.max' => '回覆內容不可超過 2000 個字',
            'admin_note.max' => '管理員備註不可超過 1000 個字',
        ]);

        // 加入回覆者和回覆時間
        $replyData = $validated + [
            'replied_by' => auth('admin')->id(),
            'replied_at' => now(),
            'is_replied' => true
        ];

        $result = $this->customerServiceService->reply($id, $replyData);

        return redirect()
            ->route('admin.customer-services.show', $id)
            ->with('result', $result);
    }


    /**
     * 刪除客服信件
     */
    public function destroy($id)
    {
        $result = $this->customerServiceService->delete($id);

        return redirect()
            ->back()
            ->with('result', $result);
    }


    /**
     * 取得統計資料（用於儀表板或統計頁面）
     */
    public function getStats()
    {
        $stats = [
            'total' => CustomerService::count(),
            'unreplied' => CustomerService::unreplied()->count(),
            'replied' => CustomerService::replied()->count(),
            'today' => CustomerService::whereDate('created_at', today())->count(),
            'this_week' => CustomerService::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month' => CustomerService::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'status' => true,
            'data' => $stats
        ]);
    }

    /**
     * 更新管理員備註
     */
    public function updateNote(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ], [
            'admin_note.max' => '管理員備註不可超過 1000 個字',
        ]);

        $result = $this->customerServiceService->updateNote($id, $validated['admin_note']);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 切換客服信件處理狀態
     */
    public function toggleStatus($id, Request $request)
    {
        $validated = $request->validate([
            'is_replied' => 'required|boolean',
        ]);

        $result = $this->customerServiceService->toggleStatus($id, $validated['is_replied']);

        return redirect()
            ->back()
            ->with('result', $result);
    }
}