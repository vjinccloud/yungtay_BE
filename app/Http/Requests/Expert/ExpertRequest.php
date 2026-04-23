<?php

namespace App\Http\Requests\Expert;

use Illuminate\Foundation\Http\FormRequest;

class ExpertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:expert_categories,id',
            'name.zh_TW' => 'required|string|max:255',
            'title.zh_TW' => 'nullable|string|max:255',
            'specialty.zh_TW' => 'nullable|string|max:1000',
            'bio.zh_TW' => 'nullable|string',
            'tags' => 'nullable|string|max:500',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'slim' => $this->isMethod('post') ? 'required' : 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'name.zh_TW.required' => '請輸入專家姓名',
            'slim.required' => '請上傳專家頭像',
        ];
    }
}
