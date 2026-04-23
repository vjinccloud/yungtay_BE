<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelTrait;

class UserCollection extends Model
{
    use HasFactory, BaseModelTrait;

    /**
     * 資料表名稱
     */
    protected $table = 'user_collections';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'user_id',
        'content_type',
        'content_id',
    ];

    /**
     * 欄位型別轉換
     */
    protected $casts = [
        'user_id' => 'integer',
        'content_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 支援的內容類型
     */
    const CONTENT_TYPES = [
        'articles' => '新聞',
        'drama' => '影音',
        'program' => '節目',
        'live' => '直播',
        'radio' => '廣播',
    ];

    /**
     * 關聯：會員
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 多型關聯：收藏的內容
     */
    public function content()
    {
        return $this->morphTo(__FUNCTION__, 'content_type', 'content_id');
    }

    /**
     * 關聯：新聞
     */
    public function article()
    {
        return $this->belongsTo(\App\Models\Article::class, 'content_id');
    }

    /**
     * 關聯：影音
     */
    public function drama()
    {
        return $this->belongsTo(\App\Models\Drama::class, 'content_id');
    }

    /**
     * 關聯：節目
     */
    public function program()
    {
        return $this->belongsTo(\App\Models\Program::class, 'content_id');
    }

    /**
     * 關聯：廣播
     */
    public function radio()
    {
        return $this->belongsTo(\App\Models\Radio::class, 'content_id');
    }

    /**
     * 取得內容類型的中文名稱
     */
    public function getContentTypeNameAttribute()
    {
        return self::CONTENT_TYPES[$this->content_type] ?? $this->content_type;
    }

    /**
     * 檢查內容類型是否有效
     */
    public static function isValidContentType($type)
    {
        return array_key_exists($type, self::CONTENT_TYPES);
    }
}
