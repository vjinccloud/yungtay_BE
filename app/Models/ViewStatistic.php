<?php

namespace App\Models;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewStatistic extends Model
{
    use HasFactory, BaseModelTrait;

    protected $table = 'view_statistics';

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'content_type',
        'content_id',
        'episode_id',
        'total_views',
        'unique_views',
        'daily_views',
        'last_view_date'
    ];

    /**
     * 屬性轉型
     */
    protected $casts = [
        'content_id' => 'integer',
        'episode_id' => 'integer',
        'total_views' => 'integer',
        'unique_views' => 'integer',
        'daily_views' => 'integer',
        'last_view_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 事件標題
     */
    public $event_title = '觀看統計';

    // ==================== 關聯方法 ====================

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
        return $query->whereNull('episode_id');
    }

    /**
     * 按觀看數排序（高到低）
     */
    public function scopeOrderByViews($query, $column = 'total_views')
    {
        return $query->orderBy($column, 'desc');
    }

    /**
     * 觀看數大於指定值
     */
    public function scopeMinViews($query, $minViews, $column = 'total_views')
    {
        return $query->where($column, '>=', $minViews);
    }

    /**
     * 最近觀看（指定天數內）
     */
    public function scopeRecentlyViewed($query, $days = 7)
    {
        return $query->where('last_view_date', '>=', now()->subDays($days));
    }

    /**
     * 熱門內容（總觀看數前 N 名）
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderByViews('total_views')->limit($limit);
    }

    // ==================== 存取器方法 ====================

    /**
     * 格式化觀看數顯示
     */
    public function getFormattedViewsAttribute(): string
    {
        if ($this->total_views >= 1000000) {
            return round($this->total_views / 1000000, 1) . 'M';
        } elseif ($this->total_views >= 1000) {
            return round($this->total_views / 1000, 1) . 'K';
        }
        return (string) $this->total_views;
    }

    /**
     * 取得觀看成長率（與昨日比較）
     */
    public function getDailyGrowthRateAttribute(): float
    {
        $yesterday = $this->daily_views ?: 0;
        $today = $this->fresh()->daily_views ?: 0;
        
        if ($yesterday == 0) {
            return $today > 0 ? 100.0 : 0.0;
        }
        
        return round((($today - $yesterday) / $yesterday) * 100, 2);
    }

    // ==================== 工具方法 ====================

    /**
     * 增加觀看數
     */
    public function incrementViews(bool $isUnique = false): void
    {
        $this->increment('total_views');
        $this->increment('daily_views');
        
        if ($isUnique) {
            $this->increment('unique_views');
        }
        
        $this->update(['last_view_date' => now()]);
    }

    /**
     * 重置日觀看數
     */
    public function resetDailyViews(): void
    {
        $this->update(['daily_views' => 0]);
    }

    /**
     * 取得內容關聯模型
     */
    public function getContentModel()
    {
        return match ($this->content_type) {
            'drama' => $this->drama,
            'program' => $this->program,
            'article' => $this->article,
            'live' => $this->live,
            'radio' => $this->radio,
            default => null,
        };
    }
}