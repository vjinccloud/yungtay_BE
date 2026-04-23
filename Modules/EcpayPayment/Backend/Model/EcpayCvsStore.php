<?php

namespace Modules\EcpayPayment\Backend\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * 綠界門市暫存 Model
 */
class EcpayCvsStore extends Model
{
    protected $table = 'ecpay_cvs_stores';

    protected $fillable = [
        'merchant_trade_no',
        'logistics_sub_type',
        'cvs_store_id',
        'cvs_store_name',
        'cvs_address',
        'cvs_telephone',
        'cvs_outside',
        'extra_data',
        'is_used',
        'expires_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * 取得超商類型名稱
     */
    public function getStoreTypeNameAttribute(): string
    {
        $types = [
            'UNIMARTC2C' => '7-11',
            'UNIMART'    => '7-11',
            'FAMIC2C'    => '全家',
            'FAMI'       => '全家',
            'HILIFEC2C'  => '萊爾富',
            'HILIFE'     => '萊爾富',
            'OKMARTC2C'  => 'OK超商',
            'OKMART'     => 'OK超商',
        ];

        return $types[$this->logistics_sub_type] ?? $this->logistics_sub_type;
    }

    /**
     * 是否為外島
     */
    public function isOutside(): bool
    {
        return $this->cvs_outside === '1';
    }

    /**
     * 標記為已使用
     */
    public function markAsUsed(): bool
    {
        return $this->update(['is_used' => true]);
    }

    /**
     * 是否已過期
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope: 依交易編號查詢
     */
    public function scopeByMerchantTradeNo($query, string $merchantTradeNo)
    {
        return $query->where('merchant_trade_no', $merchantTradeNo);
    }

    /**
     * Scope: 未使用的
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope: 未過期的
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * 建立或更新門市資訊
     */
    public static function updateOrCreateByTradeNo(string $merchantTradeNo, array $data): self
    {
        return static::updateOrCreate(
            ['merchant_trade_no' => $merchantTradeNo],
            array_merge($data, [
                'expires_at' => now()->addHours(24), // 24小時後過期
            ])
        );
    }
}
