<?php

namespace Modules\IntroVideo\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;
use Modules\IntroVideo\Model\IntroVideo;

/**
 * IntroVideo 片頭動畫 - Request
 */
class IntroVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 取得現有設定
        $record = IntroVideo::first();
        
        // 判斷是否有舊影片
        $hasVideo = $record && $record->video_path;
        
        // 檢查是否要移除影片
        $removeVideo = $this->boolean('remove_video');

        return [
            // 影片驗證
            'video' => [
                // 如果要移除影片或已有影片，則為可選；否則為必填
                ($removeVideo || $hasVideo) ? 'nullable' : 'required',
                'file',
                'mimes:mp4',
                'max:102400', // 100MB
            ],
            
            // 啟用狀態
            'is_active' => ['sometimes', 'boolean'],
            
            // 移除影片標記
            'remove_video' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'video.required' => '請上傳片頭動畫影片',
            'video.file' => '請上傳有效的檔案',
            'video.mimes' => '影片格式必須為 MP4',
            'video.max' => '影片大小不能超過 100MB',
        ];
    }

    public function attributes(): array
    {
        return [
            'video' => '片頭動畫影片',
            'is_active' => '啟用狀態',
        ];
    }
}
