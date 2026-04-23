<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BaseModelTrait;

class Notification extends Model
{
    use HasFactory, BaseModelTrait;

    protected $fillable = [
        'type',
        'recipient_type',
        'recipient_id',
        'title',
        'message',
        'data',
        'read_at',
    ];


    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // === 關聯 ===

    /**
     * 管理員關聯（當 recipient_type = 'admin' 時）
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'recipient_id')
                    ->where('recipient_type', 'admin');
    }


    // === Scopes ===

    /**
     * 未讀通知
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * 已讀通知
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * 依接收者篩選
     */
    public function scopeByRecipient($query, $recipientType, $recipientId = null)
    {
        $query->where('recipient_type', $recipientType);

        if ($recipientId !== null) {
            $query->where(function ($q) use ($recipientId) {
                $q->where('recipient_id', $recipientId)
                  ->orWhereNull('recipient_id'); // 包含全體通知
            });
        } else {
            // 如果沒有指定接收者ID，只取全體通知
            $query->whereNull('recipient_id');
        }

        return $query;
    }

    /**
     * 依類型篩選
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }


    /**
     * 篩選範圍：搜尋標題和內容
     */
    public function scopeFilter($query, array $filters)
    {
        // 搜尋關鍵字（標題和內容）
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        });
    }

    // === Accessors ===

    /**
     * 檢查是否已讀
     */
    public function getIsReadAttribute()
    {
        return !is_null($this->read_at);
    }

    /**
     * 格式化時間顯示
     */
    public function getFormattedTimeAttribute()
    {
        if (!$this->created_at) {
            return '';
        }

        $now = now();
        $diff = $this->created_at->diffInMinutes($now);

        if ($diff < 1) {
            return '剛剛';
        } elseif ($diff < 60) {
            return floor($diff) . ' 分鐘前';
        } elseif ($diff < 1440) { // 24小時
            return floor($this->created_at->diffInHours($now)) . ' 小時前';
        } elseif ($diff < 2880) { // 48小時
            return '昨天';
        } elseif ($diff < 10080) { // 7天
            return floor($this->created_at->diffInDays($now)) . ' 天前';
        } else {
            return $this->created_at->format('m/d');
        }
    }

    /**
     * 取得通知圖示 CSS class
     */
    public function getIconClassAttribute()
    {
        return match($this->type) {
            'customer_service' => 'fa fa-envelope text-primary',
            'system' => 'fa fa-cog text-warning',
            'announcement' => 'fa fa-bullhorn text-success',
            default => 'fa fa-bell text-secondary',
        };
    }

    // === 方法 ===

    /**
     * 標記為已讀
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
        return $this;
    }

    /**
     * 標記為未讀
     */
    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
        return $this;
    }

}
