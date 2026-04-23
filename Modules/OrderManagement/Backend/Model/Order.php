<?php

namespace Modules\OrderManagement\Backend\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'order_no',
        'user_id',
        'status',

        // 買家
        'buyer_name',
        'buyer_phone',
        'buyer_email',
        'buyer_note',
        'admin_note',

        // 付款
        'payment_method',
        'ecpay_merchant_trade_no',
        'ecpay_trade_no',
        'paid_at',

        // 物流
        'shipping_method',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'receiver_store_id',
        'receiver_store_name',
        'logistics_id',
        'logistics_status',
        'logistics_status_name',
        'shipped_at',
        'completed_at',

        // 金額
        'subtotal',
        'shipping_fee',
        'discount',
        'total_amount',

        // 發票
        'invoice_type',
        'invoice_no',
        'invoice_carrier_num',
        'invoice_status',

        // 取消
        'cancelled_at',
        'cancelled_reason',
    ];

    protected $casts = [
        'subtotal'      => 'integer',
        'shipping_fee'  => 'integer',
        'discount'      => 'integer',
        'total_amount'  => 'integer',
        'paid_at'       => 'datetime',
        'shipped_at'    => 'datetime',
        'completed_at'  => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    // ===== 狀態常數 =====
    const STATUS_PENDING           = 'pending';
    const STATUS_PAID              = 'paid';
    const STATUS_AWAITING_SHIPMENT = 'awaiting_shipment';
    const STATUS_SHIPPED           = 'shipped';
    const STATUS_COMPLETED         = 'completed';
    const STATUS_CANCELLED         = 'cancelled';
    const STATUS_REFUND_REQUESTED  = 'refund_requested';
    const STATUS_REFUNDED          = 'refunded';

    // ===== 付款方式 =====
    const PAYMENT_CREDIT_CARD = 'credit_card';
    const PAYMENT_ATM         = 'atm';
    const PAYMENT_CVS         = 'cvs';
    const PAYMENT_COD         = 'cod';

    // ===== 物流方式 =====
    const SHIPPING_CVS_711    = 'cvs_711';
    const SHIPPING_CVS_FAMILY = 'cvs_family';
    const SHIPPING_CVS_HILIFE = 'cvs_hilife';
    const SHIPPING_HOME       = 'home';

    // ===== 標籤對照 =====
    public static function getStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING,           'label' => '待付款',   'color' => 'warning'],
            ['value' => self::STATUS_PAID,              'label' => '已付款',   'color' => 'info'],
            ['value' => self::STATUS_AWAITING_SHIPMENT, 'label' => '待出貨',   'color' => 'primary'],
            ['value' => self::STATUS_SHIPPED,           'label' => '已出貨',   'color' => 'indigo'],
            ['value' => self::STATUS_COMPLETED,         'label' => '已完成',   'color' => 'success'],
            ['value' => self::STATUS_CANCELLED,         'label' => '已取消',   'color' => 'secondary'],
            ['value' => self::STATUS_REFUND_REQUESTED,  'label' => '退款申請', 'color' => 'danger'],
            ['value' => self::STATUS_REFUNDED,          'label' => '已退款',   'color' => 'dark'],
        ];
    }

    public static function getPaymentMethodOptions(): array
    {
        return [
            ['value' => self::PAYMENT_CREDIT_CARD, 'label' => '信用卡'],
            ['value' => self::PAYMENT_ATM,         'label' => 'ATM 轉帳'],
            ['value' => self::PAYMENT_CVS,         'label' => '超商代碼'],
            ['value' => self::PAYMENT_COD,         'label' => '貨到付款'],
        ];
    }

    public static function getShippingMethodOptions(): array
    {
        return [
            ['value' => self::SHIPPING_CVS_711,    'label' => '7-11 超商取貨'],
            ['value' => self::SHIPPING_CVS_FAMILY, 'label' => '全家超商取貨'],
            ['value' => self::SHIPPING_CVS_HILIFE, 'label' => '萊爾富超商取貨'],
            ['value' => self::SHIPPING_HOME,       'label' => '宅配到府'],
        ];
    }

    public static function getStatusLabel(string $status): string
    {
        $map = collect(self::getStatusOptions())->pluck('label', 'value');
        return $map[$status] ?? $status;
    }

    public static function getStatusColor(string $status): string
    {
        $map = collect(self::getStatusOptions())->pluck('color', 'value');
        return $map[$status] ?? 'secondary';
    }

    public static function getPaymentMethodLabel(string $method): string
    {
        $map = collect(self::getPaymentMethodOptions())->pluck('label', 'value');
        return $map[$method] ?? $method;
    }

    public static function getShippingMethodLabel(string $method): string
    {
        $map = collect(self::getShippingMethodOptions())->pluck('label', 'value');
        return $map[$method] ?? $method;
    }

    // ===== Relations =====
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class, 'order_id')->orderByDesc('created_at');
    }

    // ===== 狀態判斷 =====
    public function isPending(): bool   { return $this->status === self::STATUS_PENDING; }
    public function isPaid(): bool      { return $this->status === self::STATUS_PAID; }
    public function isShipped(): bool   { return $this->status === self::STATUS_SHIPPED; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    // ===== Scopes =====
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByOrderNo($query, string $orderNo)
    {
        return $query->where('order_no', $orderNo);
    }

    // ===== 產生訂單編號 =====
    public static function generateOrderNo(): string
    {
        // THC + YYYYMMDDHHmmss + 3碼隨機 = 20 字元
        $prefix = 'THC';
        $timestamp = now()->format('YmdHis');
        $random = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . $timestamp . $random;
    }
}
