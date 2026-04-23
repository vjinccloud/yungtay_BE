<?php

namespace Modules\FrontMenuSetting\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FrontMenuSetting 前台選單管理 - Request
 */
class FrontMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locales = config('translatable.locales', ['zh_TW' => []]);
        $primary = config('translatable.primary', 'zh_TW');

        $rules = [
            'parent_id' => 'required|integer|min:0',
            'link_type' => 'required|string|in:url,route,page,none',
            'link_url' => 'nullable|string|max:500',
            'link_target' => 'required|string|in:_self,_blank',
            'icon' => 'nullable|string|max:100',
            'seq' => 'nullable|integer|min:0|max:9999',
            'status' => 'nullable|boolean',
        ];

        foreach ($locales as $locale => $cfg) {
            $rules["title.{$locale}"] = ($locale === $primary ? 'required' : 'nullable') . '|string|max:100';
        }

        return $rules;
    }

    public function attributes(): array
    {
        $locales = config('translatable.locales', ['zh_TW' => ['label' => '中文']]);

        $attrs = [
            'parent_id' => '父層選單',
            'link_type' => '連結類型',
            'link_url' => '連結網址',
            'link_target' => '開啟方式',
            'icon' => '圖標',
            'seq' => '排序',
            'status' => '啟用狀態',
        ];

        foreach ($locales as $locale => $cfg) {
            $label = $cfg['label'] ?? $locale;
            $attrs["title.{$locale}"] = "名稱（{$label}）";
        }

        return $attrs;
    }

    public function messages(): array
    {
        $primary = config('translatable.primary', 'zh_TW');
        $label   = config("translatable.locales.{$primary}.label", '中文');

        return [
            'parent_id.required' => '請選擇父層選單',
            "title.{$primary}.required" => "請輸入名稱（{$label}）",
            'link_type.required' => '請選擇連結類型',
            'link_type.in' => '連結類型格式不正確',
            'link_target.required' => '請選擇開啟方式',
            'link_target.in' => '開啟方式格式不正確',
        ];
    }
}
