<?php

namespace App\Models;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewLog extends Model
{
    use HasFactory, BaseModelTrait;

    protected $table = 'view_logs';

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'content_type',
        'content_id',
        'episode_id',
        'user_id',
        'ip_address',
        'user_agent'
    ];

    /**
     * 屬性轉型
     */
    protected $casts = [
        'content_id' => 'integer',
        'episode_id' => 'integer', 
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 事件標題
     */
    public $event_title = '觀看記錄';

    // ==================== 關聯方法 ====================

    /**
     * 用戶關聯
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 影音關聯（當 content_type = 'drama'）
     */
    public function drama()
    {
        return $this->belongsTo(Drama::class, 'content_id');
    }

    /**
     * 節目關聯（當 content_type = 'program'）
     */
    public function program()
    {
        return $this->belongsTo(Program::class, 'content_id');
    }

    /**
     * 新聞關聯（當 content_type = 'article'）
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'content_id');
    }

    /**
     * 直播關聯（當 content_type = 'live'）
     */
    public function live()
    {
        return $this->belongsTo(Live::class, 'content_id');
    }

    /**
     * 廣播關聯（當 content_type = 'radio'）
     */
    public function radio()
    {
        return $this->belongsTo(Radio::class, 'content_id');
    }

    /**
     * 影音集數關聯（當 episode_id 存在）
     */
    public function dramaEpisode()
    {
        return $this->belongsTo(DramaEpisode::class, 'episode_id');
    }

    /**
     * 節目集數關聯（當 episode_id 存在）
     */
    public function programEpisode()
    {
        return $this->belongsTo(ProgramEpisode::class, 'episode_id');
    }

    // ==================== Scope 方法 ====================

    /**
     * 按內容類型篩選
     */
    public function scopeContentType($query, $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    /**
     * 按內容ID篩選
     */
    public function scopeContentId($query, $contentId)
    {
        return $query->where('content_id', $contentId);
    }

    /**
     * 按集數篩選
     */
    public function scopeEpisodeId($query, $episodeId)
    {
        if ($episodeId) {
            return $query->where('episode_id', $episodeId);
        }
        return $query;
    }

    /**
     * 按用戶篩選
     */
    public function scopeByUser($query, $userId)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    /**
     * 按IP地址篩選
     */
    public function scopeByIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * 按時間範圍篩選
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        if ($endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        return $query->whereDate('created_at', $startDate);
    }

    /**
     * 今日觀看記錄
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->format('Y-m-d'));
    }

    /**
     * 本週觀看記錄
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * 本月觀看記錄
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }
}