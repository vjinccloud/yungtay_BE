<?php

namespace Modules\FactorySetting\Backend\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FactorySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|array',
            'name.zh_TW' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'address' => 'nullable|array',
            'address.zh_TW' => 'nullable|string|max:500',
            'address.en' => 'nullable|string|max:500',
            'country_name' => 'nullable|array',
            'country_name.zh_TW' => 'nullable|string|max:255',
            'country_name.en' => 'nullable|string|max:255',
            'established_date' => 'nullable|date',
            // 中英文圖片
            'slim_image_zh' => 'nullable|string',
            'slim_image_en' => 'nullable|string',
            'slim_logo_zh' => 'nullable|string',
            'slim_logo_en' => 'nullable|string',
            'image_zh_cleared' => 'nullable|boolean',
            'image_en_cleared' => 'nullable|boolean',
            'logo_zh_cleared' => 'nullable|boolean',
            'logo_en_cleared' => 'nullable|boolean',
            // 中英文多圖
            'images_zh' => 'nullable|array|max:5',
            'images_zh.*' => 'nullable|string|max:500',
            'images_en' => 'nullable|array|max:5',
            'images_en.*' => 'nullable|string|max:500',
            // 中英文影片
            'visit_video_zh' => 'nullable|string|max:500',
            'visit_video_en' => 'nullable|string|max:500',
            'video_360_zh' => 'nullable|string|max:500',
            'video_360_en' => 'nullable|string|max:500',
            // 其他
            'contact_person' => 'nullable|string|max:100',
            'sort' => 'nullable|integer|min:0',
            'is_enabled' => 'boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'name.zh_TW' => '名稱（中文）',
            'name.en' => '名稱（英文）',
            'address.zh_TW' => '地址（中文）',
            'address.en' => '地址（英文）',
            'country_name.zh_TW' => '國家名（中文）',
            'country_name.en' => '國家名（英文）',
            'established_date' => '成立日期',
            'slim_image_zh' => '主圖（中文）',
            'slim_image_en' => '主圖（英文）',
            'slim_logo_zh' => 'Logo（中文）',
            'slim_logo_en' => 'Logo（英文）',
            'images_zh' => '多張圖片（中文）',
            'images_en' => '多張圖片（英文）',
            'visit_video_zh' => '訪廠影片（中文）',
            'visit_video_en' => '訪廠影片（英文）',
            'video_360_zh' => '360影片（中文）',
            'video_360_en' => '360影片（英文）',
            'sort' => '排序',
            'is_enabled' => '啟用狀態',
        ];
    }
}
