<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * 操作記錄模型
 *
 * @property int $id
 * @property int $created_by 創建人ID
 * @property string $action_type 操作類型
 * @property string $ip_address IP地址
 * @property string $details 詳細信息
 * @property string $message 操作訊息
 * @property string $attachable_type 關聯模型類型
 * @property int $attachable_id 關聯模型ID
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OperationLog extends Model
{
    use HasFactory;
    protected $fillable = ['created_by', 'action_type', 'ip_address','details','message','attachable_type','attachable_id'];
    protected $with = ['created_user'];
    //新增人員
    public function created_user()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function attachable()
    {
        return $this->morphTo();
    }


    public function scopeFilter($query, array $filters)
    {
        // 搜尋邏輯
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('message', 'like', "%{$search}%");

                // 搜尋創建用戶
                $subQuery->orWhereHas('created_user', function ($userQuery) use ($search) {
                    $userQuery->where('email', 'like', "%{$search}%");
                });
            });
        });

        // 按操作類型篩選
        $query->when($filters['action_type'] ?? null, function ($query, $actionType) {
            $query->where('action_type', $actionType);
        });

        // 按日期範圍篩選 - 開始日期
        $query->when($filters['start_date'] ?? null, function ($query, $start_date) {
            $query->whereDate('created_at', '>=', $start_date);
        });

        // 按日期範圍篩選 - 結束日期
        $query->when($filters['end_date'] ?? null, function ($query, $end_date) {
            $query->whereDate('created_at', '<=', $end_date);
        });
        //按IP地址篩選
        $query->when($filters['ip_address'] ?? null, function ($query, $ip) {
            $query->where('ip_address', 'like', "%{$ip}%");
        });

        // 按創建用戶篩選
        $query->when($filters['user_id'] ?? null, function ($query, $userId) {
            $query->WhereHas('created_user', fn($userQuery) => $userQuery->where('id', $userId));
        });
            return $query;
    }

}

// OperationLog 模型用於記錄操作日誌，包括操作人員、操作類型、IP 地址、相關資料等。
// 它使用了 Eloquent 的 HasFactory 特性，並定義了可填充的屬性和關聯。
