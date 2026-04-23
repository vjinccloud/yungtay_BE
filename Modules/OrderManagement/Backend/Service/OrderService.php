<?php

namespace Modules\OrderManagement\Backend\Service;

use Modules\OrderManagement\Backend\Repository\OrderRepository;
use Modules\OrderManagement\Backend\Model\Order;
use Modules\ProductListing\Model\Product;
use Modules\ProductListing\Model\ProductSku;
use Modules\EcpayPayment\Backend\Service\EcpayPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        protected OrderRepository $repository,
        protected EcpayPaymentService $paymentService
    ) {}

    // ========================================
    // 前台：建立訂單
    // ========================================

    /**
     * 建立訂單（前台下單）
     *
     * @param array $data 訂單資料
     * @return array
     */
    public function createOrder(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. 驗證並計算品項
            $itemsData = $this->resolveOrderItems($data['items'] ?? []);
            $subtotal = collect($itemsData)->sum('subtotal');
            $shippingFee = $this->calculateShippingFee($data['shipping_method'] ?? 'home', $subtotal);
            $discount = (int) ($data['discount'] ?? 0);
            $totalAmount = $subtotal + $shippingFee - $discount;

            if ($totalAmount < 1) {
                throw new \Exception('訂單金額不能小於 1 元');
            }

            // 2. 建立訂單
            $orderNo = Order::generateOrderNo();
            $order = $this->repository->createOrder([
                'order_no'         => $orderNo,
                'user_id'          => $data['user_id'] ?? null,
                'status'           => Order::STATUS_PENDING,
                'buyer_name'       => $data['buyer_name'],
                'buyer_phone'      => $data['buyer_phone'],
                'buyer_email'      => $data['buyer_email'] ?? null,
                'buyer_note'       => $data['buyer_note'] ?? null,
                'payment_method'   => $data['payment_method'],
                'shipping_method'  => $data['shipping_method'],
                'receiver_name'    => $data['receiver_name'],
                'receiver_phone'   => $data['receiver_phone'],
                'receiver_address' => $data['receiver_address'] ?? null,
                'receiver_store_id'   => $data['receiver_store_id'] ?? null,
                'receiver_store_name' => $data['receiver_store_name'] ?? null,
                'subtotal'         => $subtotal,
                'shipping_fee'     => $shippingFee,
                'discount'         => $discount,
                'total_amount'     => $totalAmount,
            ]);

            // 3. 建立訂單品項
            $this->repository->createOrderItems($order->id, $itemsData);

            // 4. 記錄狀態
            $this->repository->addStatusLog($order->id, null, Order::STATUS_PENDING, '訂單建立');

            // 5. 如果非貨到付款，建立綠界付款 Token
            $paymentResult = null;
            if ($data['payment_method'] !== Order::PAYMENT_COD) {
                $paymentResult = $this->createPaymentToken($order, $itemsData, $data);
            }

            // 6. 扣庫存
            $this->deductStock($itemsData);

            $order->load('items');

            return [
                'success'  => true,
                'order'    => $order,
                'payment'  => $paymentResult,
            ];
        });
    }

    /**
     * 解析訂單品項（查詢商品與SKU資料快照）
     */
    protected function resolveOrderItems(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $skuId     = $item['product_sku_id'] ?? null;
            $quantity  = (int) ($item['quantity'] ?? 1);

            $product = Product::find($productId);
            if (!$product || $product->status != 1) {
                throw new \Exception("商品 ID {$productId} 不存在或已下架");
            }

            $unitPrice = (float) $product->price;
            $skuCode = null;
            $combinationLabel = null;
            $productImage = $product->mainImage?->image_path;

            // 如果有 SKU
            if ($skuId) {
                $sku = ProductSku::find($skuId);
                if (!$sku || !$sku->status) {
                    throw new \Exception("SKU ID {$skuId} 不存在或已停用");
                }
                if ($sku->stock < $quantity) {
                    throw new \Exception("商品「{$product->name}」庫存不足（剩餘 {$sku->stock}）");
                }
                $unitPrice = (float) $sku->price;
                $skuCode = $sku->sku;
                $combinationLabel = $sku->combination_label;
            }

            $productName = is_array($product->name)
                ? ($product->name['zh_TW'] ?? reset($product->name))
                : $product->name;

            $result[] = [
                'product_id'        => $productId,
                'product_sku_id'    => $skuId,
                'product_name'      => $productName,
                'product_sku_code'  => $skuCode,
                'combination_label' => $combinationLabel,
                'unit_price'        => $unitPrice,
                'quantity'          => $quantity,
                'subtotal'          => round($unitPrice * $quantity, 2),
                'product_image'     => $productImage,
            ];
        }

        if (empty($result)) {
            throw new \Exception('訂單至少需要一個商品');
        }

        return $result;
    }

    /**
     * 計算運費
     */
    protected function calculateShippingFee(string $shippingMethod, int $subtotal): int
    {
        // 超取 60 元，宅配 100 元；滿 2000 免運
        if ($subtotal >= 2000) {
            return 0;
        }

        return match ($shippingMethod) {
            Order::SHIPPING_CVS_711,
            Order::SHIPPING_CVS_FAMILY,
            Order::SHIPPING_CVS_HILIFE => 60,
            Order::SHIPPING_HOME       => 100,
            default                    => 0,
        };
    }

    /**
     * 建立綠界付款 Token
     */
    protected function createPaymentToken(Order $order, array $items, array $data): ?array
    {
        try {
            $orderData = [
                'total_amount'    => $order->total_amount,
                'member_id'       => $order->user_id ?? 'guest',
                'phone'           => $order->buyer_phone,
                'email'           => $order->buyer_email ?? '',
                'trade_desc'      => "訂單 {$order->order_no}",
                'need_invoice'    => !empty($data['invoice_carrier_type']),
            ];

            $payItems = array_map(fn ($item) => [
                'name'     => $item['product_name'],
                'quantity' => $item['quantity'],
                'price'    => $item['unit_price'],
                'unit'     => '個',
            ], $items);

            $invoiceData = [];
            if (!empty($data['invoice_carrier_type'])) {
                $carrierType = (int) $data['invoice_carrier_type'];
                $invoiceData = [
                    'type'         => $carrierType === 3 ? 2 : 1,
                    'carrier_type' => $carrierType,
                    'carrier_num'  => $data['invoice_carrier_num'] ?? null,
                    'company_name' => $data['invoice_company_name'] ?? null,
                    'tax_id'       => $data['invoice_tax_id'] ?? null,
                    'donation'     => $carrierType === 4,
                    'love_code'    => $data['invoice_love_code'] ?? null,
                    'buyer_name'   => $order->buyer_name,
                    'buyer_email'  => $order->buyer_email,
                    'buyer_phone'  => $order->buyer_phone,
                ];
            }

            $result = $this->paymentService->createToken($orderData, $payItems, $invoiceData);

            if ($result['success']) {
                $order->update([
                    'ecpay_merchant_trade_no' => $result['merchant_trade_no'],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('建立綠界付款 Token 失敗', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 扣除庫存
     */
    protected function deductStock(array $items): void
    {
        foreach ($items as $item) {
            if (!empty($item['product_sku_id'])) {
                ProductSku::where('id', $item['product_sku_id'])
                    ->decrement('stock', $item['quantity']);
            }
        }
    }

    // ========================================
    // 後台：訂單列表
    // ========================================

    /**
     * 取得訂單列表 (DataTable 格式)
     */
    public function getOrderListForDataTable(array $params): array
    {
        $page   = (int) ($params['page'] ?? 1);
        $length = (int) ($params['length'] ?? 10);
        $search = is_array($params['search'] ?? null)
            ? ($params['search']['value'] ?? '')
            : ($params['search'] ?? '');

        return $this->repository->getOrdersForDataTable([
            'start'           => ($page - 1) * $length,
            'length'          => $length,
            'search'          => $search,
            'step_filter'     => $params['step_filter'] ?? '',
            'status'          => $params['status'] ?? '',
            'payment_method'  => $params['payment_method'] ?? '',
            'shipping_method' => $params['shipping_method'] ?? '',
            'date_from'       => $params['date_from'] ?? '',
            'date_to'         => $params['date_to'] ?? '',
        ]);
    }

    // ========================================
    // 後台：訂單詳情
    // ========================================

    /**
     * 取得訂單詳情
     */
    public function getOrderDetail(int $id): ?array
    {
        $order = $this->repository->findOrderWithDetails($id);
        if (!$order) return null;

        return [
            'id'                      => $order->id,
            'order_no'                => $order->order_no,
            'status'                  => $order->status,
            'status_label'            => Order::getStatusLabel($order->status),
            'status_color'            => Order::getStatusColor($order->status),

            // 買家資訊
            'user_id'                 => $order->user_id,
            'buyer_name'              => $order->buyer_name,
            'buyer_phone'             => $order->buyer_phone,
            'buyer_email'             => $order->buyer_email,

            // 金額
            'subtotal'                => $order->subtotal,
            'shipping_fee'            => $order->shipping_fee,
            'discount'                => $order->discount,
            'total_amount'            => $order->total_amount,

            // 付款
            'payment_method'          => $order->payment_method,
            'payment_method_label'    => Order::getPaymentMethodLabel($order->payment_method),
            'paid_at'                 => $order->paid_at?->format('Y-m-d H:i:s'),
            'ecpay_merchant_trade_no' => $order->ecpay_merchant_trade_no,
            'ecpay_trade_no'          => $order->ecpay_trade_no,

            // 物流
            'shipping_method'         => $order->shipping_method,
            'shipping_method_label'   => Order::getShippingMethodLabel($order->shipping_method),
            'shipped_at'              => $order->shipped_at?->format('Y-m-d H:i:s'),
            'receiver_name'           => $order->receiver_name,
            'receiver_phone'          => $order->receiver_phone,
            'receiver_store_id'       => $order->receiver_store_id,
            'receiver_store_name'     => $order->receiver_store_name,
            'receiver_address'        => $order->receiver_address,
            'logistics_id'            => $order->logistics_id,
            'logistics_status'        => $order->logistics_status,
            'logistics_status_name'   => $order->logistics_status_name,
            'logistics_auto_tracking' => !empty($order->logistics_id),
            'logistics_updated_at'    => null,
            'logistics_last_checked'  => null,

            // 發票
            'invoice_type'            => $order->invoice_type,
            'invoice_no'              => $order->invoice_no,
            'invoice_carrier_num'     => $order->invoice_carrier_num,
            'invoice_status'          => $order->invoice_status,

            // 備註
            'buyer_note'              => $order->buyer_note,
            'admin_note'              => $order->admin_note,
            'cancelled_reason'        => $order->cancelled_reason,

            // 時間
            'created_at'              => $order->created_at?->format('Y-m-d H:i:s'),
            'updated_at'              => $order->updated_at?->format('Y-m-d H:i:s'),

            // 品項
            'items' => $order->items->map(fn ($item) => [
                'id'                => $item->id,
                'product_name'      => $item->product_name,
                'product_sku'       => $item->product_sku_code,
                'unit_price'        => (int) $item->unit_price,
                'quantity'          => $item->quantity,
                'subtotal'          => (int) $item->subtotal,
                'options'           => $item->combination_label,
                'product_image'     => $item->product_image,
            ])->toArray(),
        ];
    }

    /**
     * 取得訂單狀態記錄
     */
    public function getStatusLogs(int $orderId): array
    {
        return $this->repository->getStatusLogs($orderId);
    }

    // ========================================
    // 後台：狀態變更
    // ========================================

    /**
     * 批次更新訂單狀態
     */
    public function batchUpdateStatus(array $orderIds, string $newStatus, ?string $note = null, string $operator = '管理員'): array
    {
        $success = 0;
        $failed  = 0;
        $errors  = [];

        foreach ($orderIds as $id) {
            $result = $this->updateStatus((int) $id, $newStatus, $note, $operator);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
                $errors[] = "ID {$id}: " . ($result['message'] ?? '未知錯誤');
            }
        }

        return [
            'success' => $failed === 0,
            'message' => "成功 {$success} 筆" . ($failed > 0 ? "，失敗 {$failed} 筆" : ''),
            'detail'  => ['success_count' => $success, 'failed_count' => $failed, 'errors' => $errors],
        ];
    }

    /**
     * 更新訂單狀態
     */
    public function updateStatus(int $orderId, string $newStatus, ?string $note = null, string $operator = '管理員'): array
    {
        $order = $this->repository->find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => '訂單不存在'];
        }

        $oldStatus = $order->status;
        $extra = [];

        // 根據新狀態附加資料
        switch ($newStatus) {
            case Order::STATUS_PAID:
                $extra['paid_at'] = now();
                break;
            case Order::STATUS_SHIPPED:
                $extra['shipped_at'] = now();
                break;
            case Order::STATUS_COMPLETED:
                $extra['completed_at'] = now();
                break;
            case Order::STATUS_CANCELLED:
                $extra['cancelled_at'] = now();
                break;
        }

        $this->repository->updateOrderStatus($orderId, $newStatus, $extra);
        $this->repository->addStatusLog($orderId, $oldStatus, $newStatus, $note, $operator);

        // 如果取消訂單，回復庫存
        if ($newStatus === Order::STATUS_CANCELLED) {
            $this->restoreStock($order);
        }

        return ['success' => true, 'message' => '狀態更新成功'];
    }

    /**
     * 回復庫存
     */
    protected function restoreStock(Order $order): void
    {
        $order->load('items');
        foreach ($order->items as $item) {
            if ($item->product_sku_id) {
                ProductSku::where('id', $item->product_sku_id)
                    ->increment('stock', $item->quantity);
            }
        }
    }

    // ========================================
    // 付款回調處理
    // ========================================

    /**
     * 處理綠界付款成功回調 — 更新訂單狀態
     */
    public function handlePaymentSuccess(string $merchantTradeNo, array $notifyData = []): void
    {
        $order = $this->repository->findByMerchantTradeNo($merchantTradeNo);
        if (!$order || !$order->isPending()) {
            return;
        }

        $order->update([
            'status'         => Order::STATUS_PAID,
            'ecpay_trade_no' => $notifyData['OrderInfo']['TradeNo'] ?? null,
            'paid_at'        => now(),
        ]);

        $this->repository->addStatusLog(
            $order->id,
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            '綠界付款成功'
        );
    }

    // ========================================
    // 前台：查詢訂單
    // ========================================

    /**
     * 前台查詢訂單（用訂單編號 + 手機）
     */
    public function queryOrderForFrontend(string $orderNo, string $phone): ?array
    {
        $order = $this->repository->findByOrderNo($orderNo);
        if (!$order || $order->buyer_phone !== $phone) {
            return null;
        }

        return $this->getOrderDetail($order->id);
    }
}
