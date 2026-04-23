<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelTrait;

class MailRecipient extends Model
{
    use BaseModelTrait;

    /**
     * 動態取得事件標題（用於操作記錄）
     */
    public function getEventTitleAttribute()
    {
        // 如果有設定 event_status（狀態切換），顯示狀態
        if (isset($this->event_status)) {
            return '收件信箱' . $this->event_status . '-' . $this->name;
        }
        
        // 否則回傳帶有名稱的格式
        return '收件信箱-' . $this->name;
    }

    protected $fillable = [
        'type_id', 'name', 'email', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'type_id' => 'integer'
    ];

    // 關聯：收件類型
    public function mailType()
    {
        return $this->belongsTo(MailType::class, 'type_id');
    }
    
    /**
     * 搜尋過濾 scope
     */
    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        return $query;
    }
}