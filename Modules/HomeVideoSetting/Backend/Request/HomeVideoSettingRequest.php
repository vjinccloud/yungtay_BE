<?php

namespace Modules\HomeVideoSetting\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HomeVideoSetting 首頁影片管理 - Request
 */
class HomeVideoSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 標題驗證
            'title.zh_TW' => ['required', 'string', 'max:100'],
            'title.en' => ['required', 'string', 'max:100'],

            // 影片路徑（由前端上傳後回傳）
            'video_zh_path' => ['nullable', 'string'],
            'video_zh_name' => ['nullable', 'string'],
            'video_en_path' => ['nullable', 'string'],
            'video_en_name' => ['nullable', 'string'],

            // 清除標記
            'video_zh_cleared' => ['nullable', 'boolean'],
            'video_en_cleared' => ['nullable', 'boolean'],

            // 排序與狀態
            'sort' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.zh_TW.required' => '請輸入中文標題',
            'title.zh_TW.max' => '中文標題不可超過 100 字',
            'title.en.required' => '請輸入英文標題',
            'title.en.max' => '英文標題不可超過 100 字',
        ];
    }
}
