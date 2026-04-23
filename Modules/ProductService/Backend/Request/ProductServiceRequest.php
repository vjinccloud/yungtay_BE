<?php

namespace Modules\ProductService\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class ProductServiceRequest extends FormRequest
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
            'sort' => 'nullable|integer|min:0',
            'is_enabled' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'name.zh_TW' => '名稱（中文）',
            'name.en' => '名稱（英文）',
            'sort' => '排序',
            'is_enabled' => '啟用狀態',
        ];
    }
}
