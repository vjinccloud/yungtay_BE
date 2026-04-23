<?php

namespace App\Http\Resources\Admin\RadioEpisode;

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
            'radio_id' => $this->radio_id,
            'season' => $this->season,
            'episode_number' => $this->episode_number,

            // 多語系時長文字
            'duration_text' => [
                'zh_TW' => $this->getTranslation('duration_text', 'zh_TW') ?? '',
                'en' => $this->getTranslation('duration_text', 'en') ?? '',
            ],

            // 多語系描述
            'description' => [
                'zh_TW' => $this->getTranslation('description', 'zh_TW') ?? '',
                'en' => $this->getTranslation('description', 'en') ?? '',
            ],

            // 音檔相關欄位
            'audio_path' => $this->audio_path,
            'audio_url' => $this->when(
                $this->audio_path,
                asset('storage/' . $this->audio_path)
            ),

            // 其他欄位
            'duration' => $this->duration,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
