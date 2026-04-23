<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelTrait;

class MailType extends Model
{
    use BaseModelTrait;

    /**
     * 事件標題（用於操作記錄）
     */
    public $event_title = '收件類型';

    protected $fillable = [
        'name', 'description', 'seq', 'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'seq' => 'integer'
    ];

    // 關聯：收件信箱
    public function mailRecipients()
    {
        return $this->hasMany(MailRecipient::class, 'type_id');
    }
}