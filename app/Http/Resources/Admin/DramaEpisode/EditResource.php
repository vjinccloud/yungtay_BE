<?php

namespace App\Http\Resources\Admin\DramaEpisode;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'drama_id' => $this->drama_id,
            'season' => $this->season ?? 1,
            
            // 多語系描述
            'description' => [
                'zh_TW' => $this->getTranslation('description', 'zh_TW') ?? '',
                'en' => $this->getTranslation('description', 'en') ?? '',
            ],
            
            // 多語系時長文字
            'duration_text' => [
                'zh_TW' => $this->getTranslation('duration_text', 'zh_TW') ?? '',
                'en' => $this->getTranslation('duration_text', 'en') ?? '',
            ],
            
            // 影片相關欄位
            'video_type' => $this->video_type,
            'youtube_url' => $this->youtube_url,
            'video_file_path' => $this->video_file_path,
            'original_filename' => $this->original_filename,
            'file_size' => $this->file_size,
            'video_format' => $this->video_format,
            
            // 根據影片類型生成顯示用的 URL
            'video_url' => $this->when(
                $this->video_file_path, 
                asset('storage/' . $this->video_file_path)
            ),
       
            
           
        ];
    }
}