<?php

namespace App\Traits;

/**
 * Episode Model 共用 Trait
 * 
 * 提供影音集數和節目集數共用的方法
 * 包含影片URL處理、集數標題等功能
 */
trait EpisodeModelTrait
{
    /**
     * 取得影片網址
     * 根據 video_type 返回對應的 URL
     */
    public function getVideoUrlAttribute()
    {
        if ($this->video_type === 'youtube') {
            return $this->youtube_url;
        } elseif ($this->video_type === 'upload') {
            return !empty($this->video_file_path) 
                ? asset('storage/' . $this->video_file_path) 
                : null;
        }
        return null;
    }

    /**
     * 是否有影片
     */
    public function getHasVideoAttribute()
    {
        return !empty($this->youtube_url) || !empty($this->video_file_path);
    }

    /**
     * 取得集數編號（動態計算）
     */
    public function getEpisodeNumberAttribute()
    {
        return $this->seq;
    }

    /**
     * 取得指定語言的集數標題
     */
    public function getEpisodeTitle($locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        if ($locale === 'en') {
            return "Episode {$this->seq}";
        } else {
            return "第{$this->seq}集";
        }
    }

    /**
     * 影片縮圖關聯
     */
    public function thumbnail()
    {
        return $this->morphImage('video_thumbnail');
    }

    // ==================== 範圍查詢 ====================

    /**
     * 依影片類型查詢
     */
    public function scopeOfVideoType($query, $type)
    {
        return $query->where('video_type', $type);
    }

    /**
     * YouTube 影片
     */
    public function scopeYoutube($query)
    {
        return $query->where('video_type', 'youtube');
    }

    /**
     * 上傳影片
     */
    public function scopeUpload($query)
    {
        return $query->where('video_type', 'upload');
    }

    /**
     * 依季數查詢
     */
    public function scopeOfSeason($query, $season)
    {
        return $query->where('season', $season);
    }
    
    /**
     * 集數專用排序查詢（覆寫 BaseModelTrait 的 scopeOrdered）
     * 先依季數排序，再依集數序號排序
     */
    public function scopeEpisodeOrdered($query)
    {
        return $query->orderBy('season')->orderBy('seq');
    }
}