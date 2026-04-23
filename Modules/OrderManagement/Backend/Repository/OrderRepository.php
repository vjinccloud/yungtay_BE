<?php

namespace Modules\OrderManagement\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\OrderManagement\Backend\Model\Order;
use Modules\OrderManagement\Backend\Model\OrderItem;
use Modules\OrderManagement\Backend\Model\OrderStatusLog;

class OrderRepository extends BaseRepository
{
    protected OrderItem $itemModel;
    protected OrderStatusLog $logModel;

    public function __construct(
        Order $model,
        OrderItem $itemModel,
        OrderStatusLog $logModel
    ) {
        parent::__construct($model);
        $this->itemModel = $itemModel;
        $this->logModel  = $logModel;
    }

    // ===== 訂單查詢 =====

    /**
     * 分頁查詢訂單列表
     */
    public function getOrdersPaginated(array $filters = [], int $perPage = 10)
    {
        $query = $this->model->with(['items']);

        // 關鍵字搜尋
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', "%{$keyword}%")
                  ->orWhere('buyer_name', 'like', "%{$keyword}%")
                  ->orWhere('buyer_phone', 'like', "%{$keyword}%")
                  ->orWhere('buyer_email', 'like', "%{$keyword}%");
            });
        }

        // 狀態篩選
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 付款方式篩選
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // 物流方式篩選
        if (!empty($filters['shipping_method'])) {
            $query->where('shipping_method', $filters['shipping_method']);
        }

        // 日期範圍
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        // 步驟篩選（特殊邏輯）
        if (!empty($filters['step_filter'])) {
            $this->applyStepFilter($query, $filters['step_filter']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * 套用步驟篩選邏輯
     */
    protected function applyStepFilter($query, string $step): void
    {
        switch ($step) {
            case 'ready_to_ship':
                $query->where(function ($q) {
                    $q->where('status', Order::STATUS_PAID)
                      ->orWhere(function ($sub) {
                          $sub->where('status', Order::STATUS_PENDING)
                              ->where('payment_method', Order::PAYMENT_COD);
                      });
                });
                break;
            case 'awaiting_shipment':
                $query->where('status', Order::STATUS_AWAITING_SHIPMENT);
                break;
            case 'shipped':
                $query->where('status', Order::STATUS_SHIPPED);
                break;
            case 'completed':
                $query->where('status', Order::STATUS_COMPLETED);
                break;
            case 'pending':
                $query->where('status', Order::STATUS_PENDING)
                      ->where('payment_method', '!=', Order::PAYMENT_COD);
                break;
            case 'cancelled':
                $query->where('status', Order::STATUS_CANCELLED);
                break;
            case 'refund_requested':
                $query->where('status', Order::STATUS_REFUND_REQUESTED);
                break;
            case 'refunded':
                $query->where('status', Order::STATUS_REFUNDED);
                break;
        }
    }

    /**
     * 取得訂單 DataTable 格式回傳
     */
    public function getOrdersForDataTable(array $filters = []): array
    {
        $start  = (int) ($filters['start'] ?? 0);
        $length = (int) ($filters['length'] ?? 10);

        $query = $this->model->with(['items']);

        // 搜尋
        if (!empty($filters['search'])) {
            $keyword = $filters['search'];
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', "%{$keyword}%")
                  ->orWhere('buyer_name', 'like', "%{$keyword}%")
                  ->orWhere('buyer_phone', 'like', "%{$keyword}%")
                  ->orWhere('buyer_email', 'like', "%{$keyword}%");
            });
        }

        // 步驟篩選
        if (!empty($filters['step_filter'])) {
            $this->applyStepFilter($query, $filters['step_filter']);
        }

        // 狀態篩選
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 付款方式篩選
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // 物流方式篩選
        if (!empty($filters['shipping_method'])) {
            $query->where('shipping_method', $filters['shipping_method']);
        }

        // 日期範圍
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $total = $query->count();

        $data = $query->orderByDesc('created_at')
                      ->skip($start)
                      ->take($length)
                      ->get()
                      ->map(fn ($order) => $this->formatOrderListItem($order))
                      ->toArray();

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

    /**
     * 格式化訂單列表項目
     */
    public function formatOrderListItem(Order $order): array
    {
        return [
            'id'                      => $order->id,
            'order_no'                => $order->order_no,
            'buyer_name'              => $order->buyer_name,
            'buyer_phone'             => $order->buyer_phone,
            'buyer_email'             => $order->buyer_email,
            'total_amount'            => $order->total_amount,
            'status'                  => $order->status,
            'status_label'            => Order::getStatusLabel($order->status),
            'status_color'            => Order::getStatusColor($order->status),
            'payment_method'          => $order->payment_method,
            'payment_method_label'    => Order::getPaymentMethodLabel($order->payment_method),
            'shipping_method'         => $order->shipping_method,
            'shipping_method_label'   => Order::getShippingMethodLabel($order->shipping_method),
            'items_count'             => $order->items->count(),
            'paid_at'                 => $order->paid_at?->format('Y-m-d H:i:s'),
            'shipped_at'              => $order->shipped_at?->format('Y-m-d H:i:s'),
            'created_at'              => $order->created_at?->format('Y-m-d H:i:s'),
            'logistics_status_name'   => $order->logistics_status_name,
            'logistics_auto_updating' => !empty($order->logistics_id),
            'receiver_store_name'     => $order->receiver_store_name,
            'receiver_address'        => $order->receiver_address,
        ];
    }

    /**
     * 取得訂單詳情
     */
    public function findOrderWithDetails(int $id): ?Order
    {
        return $this->model->with(['items', 'statusLogs'])->find($id);
    }

    /**
     * 依訂單編號查詢
     */
    public function findByOrderNo(string $orderNo): ?Order
    {
        return $this->model->byOrderNo($orderNo)->first();
    }

    /**
     * 依綠界交易編號查詢
     */
    public function findByMerchantTradeNo(string $merchantTradeNo): ?Order
    {
        return $this->model->where('ecpay_merchant_trade_no', $merchantTradeNo)->first();
    }

    // ===== 訂單操作 =====

    /**
     * 建立訂單
     */
    public function createOrder(array $data): Order
    {
        return $this->model->create($data);
    }

    /**
     * 建立訂單品項
     */
    public function createOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $item['order_id'] = $orderId;
            $this->itemModel->create($item);
        }
    }

    /**
     * 新增狀態變更記錄
     */
    public function addStatusLog(int $orderId, ?string $fromStatus, string $toStatus, ?string $note = null, string $operator = '系統'): void
    {
        $this->logModel->create([
            'order_id'    => $orderId,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'note'        => $note,
            'operator'    => $operator,
        ]);
    }

    /**
     * 取得訂單狀態記錄
     */
    public function getStatusLogs(int $orderId): array
    {
        return $this->logModel
            ->where('order_id', $orderId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($log) => [
                'status'       => $log->to_status,
                'status_label' => Order::getStatusLabel($log->to_status),
                'status_color' => Order::getStatusColor($log->to_status),
                'note'         => $log->note,
                'operator'     => $log->operator,
                'created_at'   => $log->created_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }

    /**
     * 更新訂單狀態
     */
    public function updateOrderStatus(int $orderId, string $newStatus, array $extra = []): bool
    {
        $order = $this->model->findOrFail($orderId);
        $data = array_merge(['status' => $newStatus], $extra);
        return $order->update($data);
    }
}
