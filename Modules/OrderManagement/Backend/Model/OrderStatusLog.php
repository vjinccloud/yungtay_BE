<?php

namespace Modules\OrderManagement\Backend\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusLog extends Model
{
    protected $table = 'order_status_logs';

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'note',
        'operator',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * 取得狀態標籤
     */
    public function getStatusLabelAttribute(): string
    {
        return Order::getStatusLabel($this->to_status);
    }

    public function getStatusColorAttribute(): string
    {
        return Order::getStatusColor($this->to_status);
    }
}
