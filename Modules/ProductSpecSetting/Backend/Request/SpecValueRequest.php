<?php

namespace Modules\ProductSpecSetting\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class SpecValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name.zh_TW' => 'required|string|max:100',
            'name.en' => 'nullable|string|max:100',
            'seq' => 'nullable|integer|min:0|max:9999',
            'status' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'name.zh_TW' => '中文名稱',
            'name.en' => '英文名稱',
            'seq' => '排序',
            'status' => '啟用狀態',
        ];
    }

    public function messages(): array
    {
        return [
            'name.zh_TW.required' => '請輸入規格值中文名稱',
        ];
    }
}
