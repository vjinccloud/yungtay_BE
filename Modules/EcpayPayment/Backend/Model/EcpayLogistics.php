<?php

namespace Modules\EcpayPayment\Backend\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * 綠界物流記錄 Model
 */
class EcpayLogistics extends Model
{
    protected $table = 'ecpay_logistics';

    protected $fillable = [
        'order_id',
        'order_number',
        'merchant_trade_no',
        'all_pay_logistics_id',
        'cvs_payment_no',
        'cvs_validation_no',
        'logistics_type',
        'logistics_sub_type',
        'goods_amount',
        'is_collection',
        'sender_name',
        'sender_phone',
        'receiver_name',
        'receiver_phone',
        'receiver_store_id',
        'receiver_store_name',
        'receiver_store_address',
        'logistics_status',
        'logistics_status_name',
        'rtn_code',
        'rtn_msg',
        'booking_note',
        'update_status_date',
        'request_data',
        'response_data',
        'callback_data',
        'remark',
    ];

    protected $casts = [
        'goods_amount' => 'integer',
        'rtn_code' => 'integer',
        'booking_note' => 'datetime',
        'update_status_date' => 'datetime',
        'request_data' => 'array',
        'response_data' => 'array',
        'callback_data' => 'array',
    ];

    /**
     * 物流狀態對照 (常見狀態)
     */
    const STATUS_MAP = [
        '300' => '訂單處理中',
        '310' => '已產生寄件單',
        '3003' => '門市驗收成功',
        '3006' => '門市驗收失敗',
        '2030' => '配達已取件',
        '2063' => '已送達門市',
        '2067' => '已取貨',
        '2073' => '已退貨(未領)',
        '3018' => '已退貨(領取)',
        '3020' => '退貨完成',
    ];

    /**
     * 取得狀態名稱
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUS_MAP[$this->logistics_status] ?? $this->logistics_status_name ?? '未知狀態';
    }

    /**
     * 是否已送達門市
     */
    public function isArrivedAtStore(): bool
    {
        return in_array($this->logistics_status, ['2063']);
    }

    /**
     * 是否已取貨
     */
    public function isPickedUp(): bool
    {
        return in_array($this->logistics_status, ['2067']);
    }

    /**
     * 是否已退貨
     */
    public function isReturned(): bool
    {
        return in_array($this->logistics_status, ['2073', '3018', '3020']);
    }

    /**
     * 取得超商類型名稱
     */
    public function getStoreTypeNameAttribute(): string
    {
        $types = [
            'UNIMARTC2C' => '7-11',
            'FAMIC2C'    => '全家',
            'HILIFEC2C'  => '萊爾富',
            'OKMARTC2C'  => 'OK超商',
        ];

        return $types[$this->logistics_sub_type] ?? $this->logistics_sub_type;
    }

    /**
     * Scope: 依物流編號查詢
     */
    public function scopeByLogisticsId($query, string $logisticsId)
    {
        return $query->where('all_pay_logistics_id', $logisticsId);
    }

    /**
     * Scope: 依交易編號查詢
     */
    public function scopeByMerchantTradeNo($query, string $merchantTradeNo)
    {
        return $query->where('merchant_trade_no', $merchantTradeNo);
    }

    /**
     * Scope: 依訂單查詢
     */
    public function scopeByOrderId($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
