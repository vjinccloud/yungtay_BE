<?php

namespace Modules\MenuSetting\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class MenuSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'parent_id' => 'required|integer|min:0',
            'type' => 'required|integer|in:0,1',
            'url' => 'nullable|string|max:255',
            'url_name' => 'nullable|string|max:255',
            'icon_image' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
            'seq' => 'nullable|integer|min:0|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => '選單名稱',
            'parent_id' => '父層選單',
            'type' => '顯示類型',
            'url' => '連結網址',
            'url_name' => '路由名稱',
            'icon_image' => '圖標',
            'status' => '啟用狀態',
            'seq' => '排序',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '請輸入選單名稱',
            'parent_id.required' => '請選擇父層選單',
            'type.required' => '請選擇顯示類型',
            'type.in' => '顯示類型格式不正確',
        ];
    }
}
