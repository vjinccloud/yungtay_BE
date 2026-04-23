<?php

namespace Modules\EcpayPayment\Backend\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 綠界付款記錄 Model
 */
class EcpayPayment extends Model
{
    protected $table = 'ecpay_payments';

    protected $fillable = [
        // 交易資訊
        'merchant_trade_no',
        'trade_no',
        'total_amount',
        'payment_type',
        'payment_type_charge_fee',
        'trade_status',
        'rtn_code',
        'rtn_msg',
        'trade_date',
        'payment_date',
        
        // 會員資訊
        'member_id',
        'member_email',
        'member_phone',
        'pay_token',
        
        // 原始資料
        'request_data',
        'response_data',
        'notify_data',
        'remark',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'rtn_code' => 'integer',
        'trade_date' => 'datetime',
        'payment_date' => 'datetime',
        'request_data' => 'array',
        'response_data' => 'array',
        'notify_data' => 'array',
    ];

    /**
     * 交易狀態常數
     */
    const STATUS_PENDING    = 'pending';     // 待付款
    const STATUS_PROCESSING = 'processing';  // 處理中
    const STATUS_PAID       = 'paid';        // 已付款
    const STATUS_FAILED     = 'failed';      // 付款失敗
    const STATUS_REFUNDED   = 'refunded';    // 已退款

    /**
     * 關聯發票（一對多，可能有折讓、重開等情況）
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(EcpayInvoice::class, 'ecpay_payment_id');
    }

    /**
     * 取得最新的發票
     */
    public function latestInvoice(): HasOne
    {
        return $this->hasOne(EcpayInvoice::class, 'ecpay_payment_id')
                    ->latestOfMany();
    }

    /**
     * 取得有效的發票（已開立且未作廢）
     */
    public function activeInvoice(): HasOne
    {
        return $this->hasOne(EcpayInvoice::class, 'ecpay_payment_id')
                    ->where('status', EcpayInvoice::STATUS_ISSUED);
    }

    /**
     * 狀態標籤對照
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING    => '待付款',
            self::STATUS_PROCESSING => '處理中',
            self::STATUS_PAID       => '已付款',
            self::STATUS_FAILED     => '付款失敗',
            self::STATUS_REFUNDED   => '已退款',
        ];
    }

    /**
     * 取得狀態標籤
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->trade_status] ?? $this->trade_status;
    }

    /**
     * 是否已付款
     */
    public function isPaid(): bool
    {
        return $this->trade_status === self::STATUS_PAID;
    }

    /**
     * 是否付款失敗
     */
    public function isFailed(): bool
    {
        return $this->trade_status === self::STATUS_FAILED;
    }

    /**
     * 是否待付款
     */
    public function isPending(): bool
    {
        return $this->trade_status === self::STATUS_PENDING;
    }

    /**
     * 是否有有效發票
     */
    public function hasActiveInvoice(): bool
    {
        return $this->invoices()
                    ->where('status', EcpayInvoice::STATUS_ISSUED)
                    ->exists();
    }

    /**
     * 是否需要開立發票
     */
    public function needsInvoice(): bool
    {
        return $this->isPaid() && !$this->hasActiveInvoice();
    }

    /**
     * 標記為已付款
     */
    public function markAsPaid(array $notifyData = []): bool
    {
        return $this->update([
            'trade_status' => self::STATUS_PAID,
            'payment_date' => now(),
            'notify_data' => $notifyData,
        ]);
    }

    /**
     * 標記為付款失敗
     */
    public function markAsFailed(array $notifyData = [], ?string $rtnMsg = null): bool
    {
        return $this->update([
            'trade_status' => self::STATUS_FAILED,
            'rtn_msg' => $rtnMsg ?? $this->rtn_msg,
            'notify_data' => $notifyData,
        ]);
    }

    /**
     * Scope: 依狀態篩選
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('trade_status', $status);
    }

    /**
     * Scope: 已付款
     */
    public function scopePaid($query)
    {
        return $query->where('trade_status', self::STATUS_PAID);
    }

    /**
     * Scope: 待付款
     */
    public function scopePending($query)
    {
        return $query->where('trade_status', self::STATUS_PENDING);
    }

    /**
     * Scope: 依交易編號查詢
     */
    public function scopeByMerchantTradeNo($query, string $merchantTradeNo)
    {
        return $query->where('merchant_trade_no', $merchantTradeNo);
    }
}
