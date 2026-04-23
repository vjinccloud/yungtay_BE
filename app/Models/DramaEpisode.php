<?php
// app/Models/DramaEpisode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use App\Traits\EpisodeModelTrait;

class DramaEpisode extends Model
{
    use HasTranslations;
    use BaseModelTrait;
    use EpisodeModelTrait;

    protected $fillable = [
        'drama_id',
        'description',
        'season',
        'duration_text',
        'video_type',
        'youtube_url',
        'video_file_path',
        'original_filename',
        'file_size',
        'video_format',
        'seq',
        'created_by',
        'updated_by'
    ];

    public $translatable = [
        'description',
        'duration_text'
    ];

    protected $casts = [
        'file_size' => 'decimal:2',
        'seq' => 'integer',
    ];

    // ==================== 關聯 ====================

    /**
     * 所屬影音
     */
    public function drama()
    {
        return $this->belongsTo(Drama::class);
    }

    // ==================== Accessors ====================
    // (由 EpisodeModelTrait 提供)

    // ==================== 範圍查詢 ====================
    // (部分由 EpisodeModelTrait 提供)

    /**
     * 依影音查詢
     */
    public function scopeOfDrama($query, $dramaId)
    {
        return $query->where('drama_id', $dramaId);
    }
    
    /**
     * 覆寫 BaseModelTrait 的 scopeOrdered
     * 集數需要先依季數再依序號排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('season')->orderBy('seq');
    }
}
