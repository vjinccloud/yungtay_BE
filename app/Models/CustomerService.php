<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelTrait;

class CustomerService extends Model
{
    use BaseModelTrait;

    /**
     * 事件標題（用於操作記錄）
     */
    public $event_title = '客服訊息';

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'subject',
        'message',
        'admin_note',
        'is_replied',
        'reply_subject',
        'reply_content',
        'replied_at',
        'replied_by'
    ];

    /**
     * 屬性類型轉換
     */
    protected $casts = [
        'is_replied' => 'boolean',
        'replied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 關聯：會員
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 關聯：回覆者（管理員）
     */
    public function repliedBy()
    {
        return $this->belongsTo(AdminUser::class, 'replied_by');
    }

    /**
     * 查詢範圍：未回覆的
     */
    public function scopeUnreplied($query)
    {
        return $query->where('is_replied', false);
    }

    /**
     * 查詢範圍：已回覆的
     */
    public function scopeReplied($query)
    {
        return $query->where('is_replied', true);
    }

    /**
     * 查詢範圍：搜尋過濾
     */
    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_replied'])) {
            $query->where('is_replied', $filters['is_replied']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query;
    }
}