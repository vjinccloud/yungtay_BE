<?php

namespace Modules\ProductSpecSetting\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class SpecGroupRequest extends FormRequest
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
            'seq'    => 'nullable|integer|min:0|max:9999',
            'status' => 'nullable|boolean',
            'values'       => 'nullable|array',
            'values.*.id'  => 'nullable|integer',
            'values.*.seq' => 'nullable|integer|min:0|max:9999',
            'values.*.status' => 'nullable|boolean',
        ];

        foreach ($locales as $locale => $cfg) {
            $rules["name.{$locale}"]          = ($locale === $primary ? 'required' : 'nullable') . '|string|max:100';
            $rules["values.*.name.{$locale}"] = ($locale === $primary ? 'required' : 'nullable') . '|string|max:100';
        }

        return $rules;
    }

    public function attributes(): array
    {
        $locales = config('translatable.locales', ['zh_TW' => ['label' => '中文']]);

        $attrs = [
            'seq'    => '排序',
            'status' => '啟用狀態',
        ];

        foreach ($locales as $locale => $cfg) {
            $label = $cfg['label'] ?? $locale;
            $attrs["name.{$locale}"]          = "群組名稱（{$label}）";
            $attrs["values.*.name.{$locale}"] = "規格值名稱（{$label}）";
        }

        $attrs['values.*.seq']    = '規格值排序';
        $attrs['values.*.status'] = '規格值啟用狀態';

        return $attrs;
    }

    public function messages(): array
    {
        return [
            'name.zh_TW.required' => '請輸入規格群組中文名稱',
            'values.*.name.zh_TW.required' => '請輸入規格值中文名稱',
        ];
    }
}
