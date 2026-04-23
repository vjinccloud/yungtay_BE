<?php

namespace Modules\EcpayPayment\Backend\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 綠界電子發票 Model
 */
class EcpayInvoice extends Model
{
    protected $table = 'ecpay_invoices';

    protected $fillable = [
        // 關聯
        'ecpay_payment_id',
        
        // 發票基本資訊
        'invoice_no',
        'random_number',
        'invoice_date',
        'status',
        
        // 發票類型
        'type',
        'carrier_type',
        'carrier_num',
        
        // 公司發票
        'company_name',
        'tax_id',
        
        // 捐贈
        'donation',
        'love_code',
        
        // 金額
        'sales_amount',
        'tax_amount',
        'total_amount',
        
        // 品項
        'items',
        
        // 寄送資訊
        'print_name',
        'print_address',
        'print_phone',
        
        // 買受人
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        
        // 綠界回傳
        'relate_number',
        'rtn_code',
        'rtn_msg',
        
        // 作廢/折讓
        'void_date',
        'void_reason',
        'allowance_amount',
        'allowance_date',
        
        // 原始資料
        'request_data',
        'response_data',
        'remark',
    ];

    protected $casts = [
        'ecpay_payment_id' => 'integer',
        'type' => 'integer',
        'donation' => 'boolean',
        'sales_amount' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'allowance_amount' => 'integer',
        'invoice_date' => 'datetime',
        'void_date' => 'datetime',
        'allowance_date' => 'datetime',
        'items' => 'array',
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    /**
     * 發票狀態常數
     */
    const STATUS_PENDING   = 'pending';    // 待開立
    const STATUS_ISSUED    = 'issued';     // 已開立
    const STATUS_VOID      = 'void';       // 已作廢
    const STATUS_ALLOWANCE = 'allowance';  // 已折讓

    /**
     * 發票類型常數
     */
    const TYPE_PERSONAL = 1;  // 個人
    const TYPE_COMPANY  = 2;  // 公司

    /**
     * 載具類型常數
     */
    const CARRIER_NONE    = '';   // 無載具（紙本）
    const CARRIER_ECPAY   = '1';  // 綠界電子發票載具
    const CARRIER_PHONE   = '2';  // 手機條碼
    const CARRIER_CITIZEN = '3';  // 自然人憑證

    /**
     * 關聯付款記錄
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(EcpayPayment::class, 'ecpay_payment_id');
    }

    /**
     * 發票狀態標籤對照
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING   => '待開立',
            self::STATUS_ISSUED    => '已開立',
            self::STATUS_VOID      => '已作廢',
            self::STATUS_ALLOWANCE => '已折讓',
        ];
    }

    /**
     * 發票類型標籤對照
     */
    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_PERSONAL => '個人',
            self::TYPE_COMPANY  => '公司',
        ];
    }

    /**
     * 載具類型標籤對照
     */
    public static function getCarrierTypeLabels(): array
    {
        return [
            self::CARRIER_NONE    => '紙本發票',
            self::CARRIER_ECPAY   => '綠界電子發票載具',
            self::CARRIER_PHONE   => '手機條碼',
            self::CARRIER_CITIZEN => '自然人憑證',
        ];
    }

    /**
     * 取得狀態標籤
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    /**
     * 取得類型標籤
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypeLabels()[$this->type] ?? '未知';
    }

    /**
     * 取得載具類型標籤
     */
    public function getCarrierTypeLabelAttribute(): string
    {
        return self::getCarrierTypeLabels()[$this->carrier_type ?? ''] ?? '未知';
    }

    /**
     * 是否已開立
     */
    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    /**
     * 是否已作廢
     */
    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    /**
     * 是否待開立
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * 是否為公司發票
     */
    public function isCompanyInvoice(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }

    /**
     * 是否為捐贈發票
     */
    public function isDonation(): bool
    {
        return $this->donation === true;
    }

    /**
     * 是否使用載具
     */
    public function hasCarrier(): bool
    {
        return !empty($this->carrier_type);
    }

    /**
     * 是否為紙本發票
     */
    public function isPaperInvoice(): bool
    {
        return !$this->hasCarrier() && !$this->isDonation();
    }

    /**
     * 取得完整發票號碼（含隨機碼）
     */
    public function getFullInvoiceNoAttribute(): string
    {
        if (empty($this->invoice_no)) {
            return '';
        }
        return $this->invoice_no . '-' . ($this->random_number ?? '');
    }

    /**
     * Scope: 已開立的發票
     */
    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    /**
     * Scope: 待開立的發票
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: 依付款記錄
     */
    public function scopeByPayment($query, int $paymentId)
    {
        return $query->where('ecpay_payment_id', $paymentId);
    }
}
