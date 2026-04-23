<?php

namespace Modules\OrderManagement\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\OrderManagement\Backend\Service\OrderService;
use Modules\OrderManagement\Backend\Model\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * 訂單列表頁
     */
    public function index(Request $request)
    {
        $list = $this->orderService->getOrderListForDataTable($request->all());

        return Inertia::render('Admin/OrderManagement/Index', [
            'list'                  => $list,
            'statusOptions'         => Order::getStatusOptions(),
            'paymentMethodOptions'  => Order::getPaymentMethodOptions(),
            'shippingMethodOptions' => Order::getShippingMethodOptions(),
        ]);
    }

    /**
     * 訂單詳情頁
     */
    public function show($id)
    {
        $order = $this->orderService->getOrderDetail((int) $id);

        if (!$order) {
            return redirect()->route('admin.orders.index')
                             ->with('error', '訂單不存在');
        }

        return Inertia::render('Admin/OrderManagement/Detail', [
            'order'             => $order,
            'statusOptions'     => Order::getStatusOptions(),
            'statusLogs'        => $this->orderService->getStatusLogs((int) $id),
            'logisticsTracking' => [], // TODO: 接物流追蹤 API
        ]);
    }

    /**
     * 更新訂單狀態
     *
     * POST /admin/orders/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'note'   => 'nullable|string|max:500',
        ]);

        $result = $this->orderService->updateStatus(
            (int) $id,
            $request->input('status'),
            $request->input('note'),
            '管理員'
        );

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->back()->with('result', $result);
    }

    /**
     * 批次更新訂單狀態
     *
     * POST /admin/orders/batch-status
     */
    public function batchUpdateStatus(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
            'status' => 'required|string',
            'note'   => 'nullable|string|max:500',
        ]);

        $result = $this->orderService->batchUpdateStatus(
            $request->input('ids'),
            $request->input('status'),
            $request->input('note'),
            '管理員'
        );

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->back()->with('result', $result);
    }

    /**
     * 更新管理員備註
     *
     * POST /admin/orders/{id}/note
     */
    public function updateNote(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['admin_note' => $request->input('admin_note')]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('result', ['success' => true, 'message' => '備註已更新']);
    }
}
