<?php

namespace Modules\IntroVideo\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * IntroVideo 片頭動畫 - Model
 */
class IntroVideo extends Model
{
    use BaseModelTrait;

    protected $table = 'intro_videos';

    protected $fillable = [
        'video_path',
        'video_original_name',
        'video_size',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'video_size' => 'integer',
    ];

    /**
     * 取得影片完整 URL
     */
    public function getVideoUrlAttribute()
    {
        if (!$this->video_path) {
            return null;
        }
        
        return Storage::disk('public')->url($this->video_path);
    }

    /**
     * 取得影片大小（格式化）
     */
    public function getVideoSizeFormattedAttribute()
    {
        if (!$this->video_size) {
            return null;
        }

        $bytes = $this->video_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
