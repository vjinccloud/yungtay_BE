<?php

namespace App\Http\Requests\Live;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\YouTubeHelper;

class LiveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title.zh_TW' => 'required|string|max:255',
            'title.en' => 'required|string|max:255',
            'youtube_url' => 'required|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.zh_TW.required' => '請輸入中文標題',
            'title.zh_TW.max' => '中文標題不能超過255個字元',
            'title.en.required' => '請輸入英文標題',
            'title.en.max' => '英文標題不能超過255個字元',
            'youtube_url.required' => '請輸入YouTube直播網址',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title.zh_TW' => '中文標題',
            'title.en' => '英文標題',
            'youtube_url' => 'YouTube直播網址',
            'is_active' => '狀態',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 自訂驗證：YouTube URL 格式嚴格檢查
            $url = $this->input('youtube_url');
            if ($url && !YouTubeHelper::isValidYouTubeUrl($url)) {
                $validator->errors()->add('youtube_url', '請輸入有效的 YouTube 影片網址（支援格式：youtube.com/watch?v=xxx 或 youtu.be/xxx）');
            }
        });
    }

    /**
     * 準備資料進行驗證（在驗證前自動轉換 YouTube URL）
     */
    protected function prepareForValidation()
    {
        // 如果有 YouTube URL，自動轉換為標準格式
        if ($this->input('youtube_url')) {
            $url = $this->input('youtube_url');
            $standardUrl = YouTubeHelper::convertToStandardUrl($url);
            
            if ($standardUrl) {
                // 統一轉換為標準格式
                $this->merge([
                    'youtube_url' => $standardUrl
                ]);
            }
        }
    }
}