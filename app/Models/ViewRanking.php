<?php

namespace App\Models;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewRanking extends Model
{
    use HasFactory, BaseModelTrait;

    protected $table = 'view_rankings';

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'period_type',
        'period_date',
        'content_type',
        'content_id',
        'ranking',
        'view_count',
        'unique_count',
        'growth_rate'
    ];

    /**
     * 屬性轉型
     */
    protected $casts = [
        'period_date' => 'date',
        'content_id' => 'integer',
        'ranking' => 'integer',
        'view_count' => 'integer',
        'unique_count' => 'integer',
        'growth_rate' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 事件標題
     */
    public $event_title = '排行榜';

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

    // ==================== Scope 方法 ====================

    /**
     * 按期間類型篩選
     */
    public function scopePeriodType($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * 按期間日期篩選
     */
    public function scopePeriodDate($query, $periodDate)
    {
        return $query->whereDate('period_date', $periodDate);
    }

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
     * 前 N 名排行
     */
    public function scopeTopRankings($query, $limit = 10)
    {
        return $query->where('ranking', '<=', $limit)
                    ->orderBy('ranking', 'asc');
    }

    /**
     * 日排行榜
     */
    public function scopeDaily($query, $date = null)
    {
        $date = $date ?: now()->format('Y-m-d');
        return $query->where('period_type', 'daily')
                    ->whereDate('period_date', $date);
    }

    /**
     * 週排行榜
     */
    public function scopeWeekly($query, $date = null)
    {
        $date = $date ?: now()->startOfWeek()->format('Y-m-d');
        return $query->where('period_type', 'weekly')
                    ->whereDate('period_date', $date);
    }

    /**
     * 月排行榜
     */
    public function scopeMonthly($query, $date = null)
    {
        $date = $date ?: now()->startOfMonth()->format('Y-m-d');
        return $query->where('period_type', 'monthly')
                    ->whereDate('period_date', $date);
    }

    /**
     * 年度排行榜
     */
    public function scopeYearly($query, $date = null)
    {
        $date = $date ?: now()->startOfYear()->format('Y-m-d');
        return $query->where('period_type', 'yearly')
                    ->whereDate('period_date', $date);
    }

    /**
     * 按排名排序
     */
    public function scopeOrderByRanking($query, $direction = 'asc')
    {
        return $query->orderBy('ranking', $direction);
    }

    /**
     * 按觀看數排序
     */
    public function scopeOrderByViews($query, $direction = 'desc')
    {
        return $query->orderBy('view_count', $direction);
    }

    /**
     * 正成長排行（成長率 > 0）
     */
    public function scopePositiveGrowth($query)
    {
        return $query->where('growth_rate', '>', 0);
    }

    /**
     * 最新排行榜
     */
    public function scopeLatest($query, $periodType = null)
    {
        $query = $query->orderBy('period_date', 'desc');
        
        if ($periodType) {
            $query->where('period_type', $periodType);
        }
        
        return $query;
    }

    // ==================== 存取器方法 ====================

    /**
     * 格式化觀看數顯示
     */
    public function getFormattedViewsAttribute(): string
    {
        if ($this->view_count >= 1000000) {
            return round($this->view_count / 1000000, 1) . 'M';
        } elseif ($this->view_count >= 1000) {
            return round($this->view_count / 1000, 1) . 'K';
        }
        return (string) $this->view_count;
    }

    /**
     * 格式化成長率顯示
     */
    public function getFormattedGrowthRateAttribute(): string
    {
        if ($this->growth_rate > 0) {
            return '+' . $this->growth_rate . '%';
        } elseif ($this->growth_rate < 0) {
            return $this->growth_rate . '%';
        }
        return '0%';
    }

    /**
     * 期間類型顯示文字
     */
    public function getPeriodTypeTextAttribute(): string
    {
        return match ($this->period_type) {
            'daily' => '日榜',
            'weekly' => '週榜',
            'monthly' => '月榜',
            'yearly' => '年榜',
            default => '未知',
        };
    }

    /**
     * 排名徽章樣式
     */
    public function getRankingBadgeClassAttribute(): string
    {
        return match ($this->ranking) {
            1 => 'badge-gold',
            2 => 'badge-silver',
            3 => 'badge-bronze',
            default => 'badge-default',
        };
    }

    /**
     * 是否為前三名
     */
    public function getIsTopThreeAttribute(): bool
    {
        return $this->ranking <= 3;
    }

    /**
     * 成長趨勢
     */
    public function getGrowthTrendAttribute(): string
    {
        if ($this->growth_rate > 10) {
            return 'hot'; // 熱門上升
        } elseif ($this->growth_rate > 0) {
            return 'up'; // 上升
        } elseif ($this->growth_rate < -10) {
            return 'cold'; // 快速下降
        } elseif ($this->growth_rate < 0) {
            return 'down'; // 下降
        }
        return 'stable'; // 穩定
    }

    // ==================== 工具方法 ====================

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

    /**
     * 取得內容標題
     */
    public function getContentTitleAttribute(): string
    {
        $content = $this->getContentModel();
        
        if (!$content) {
            return '未知內容';
        }

        // 如果有多語言字段，優先取中文
        if (method_exists($content, 'getTranslation')) {
            return $content->getTranslation('title', 'zh_TW') ?: $content->title;
        }

        return $content->title ?? '無標題';
    }

    /**
     * 更新排名資訊
     */
    public function updateRanking(int $ranking, int $viewCount, int $uniqueCount = null, float $growthRate = null): void
    {
        $this->update([
            'ranking' => $ranking,
            'view_count' => $viewCount,
            'unique_count' => $uniqueCount ?? $this->unique_count,
            'growth_rate' => $growthRate ?? $this->growth_rate,
        ]);
    }

    /**
     * 檢查是否為當期排行榜
     */
    public function isCurrent(string $periodType = null): bool
    {
        $periodType = $periodType ?: $this->period_type;
        
        $currentDate = match ($periodType) {
            'daily' => now()->format('Y-m-d'),
            'weekly' => now()->startOfWeek()->format('Y-m-d'),
            'monthly' => now()->startOfMonth()->format('Y-m-d'),
            'yearly' => now()->startOfYear()->format('Y-m-d'),
            default => now()->format('Y-m-d'),
        };

        return $this->period_date->format('Y-m-d') === $currentDate;
    }
}