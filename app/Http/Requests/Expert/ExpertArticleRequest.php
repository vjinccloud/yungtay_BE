<?php

namespace App\Http\Requests\Expert;

use Illuminate\Foundation\Http\FormRequest;

class ExpertArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expert_id' => 'required|exists:experts,id',
            'title.zh_TW' => 'required|string|max:255',
            'content.zh_TW' => 'required|string',
            'description' => 'nullable|string|max:500',
            'tags' => 'nullable|string|max:500',
            'sdgs' => 'nullable|array|max:5',
            'sdgs.*' => 'integer|between:1,17',
            'published_date' => 'required|date',
            'is_active' => 'boolean',
            'slim' => $this->isMethod('post') ? 'required' : 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'expert_id.required' => '請選擇專家',
            'expert_id.exists' => '所選專家不存在',
            'title.zh_TW.required' => '請輸入文章標題',
            'content.zh_TW.required' => '請輸入文章內容',
            'published_date.required' => '請選擇發布日期',
            'slim.required' => '請上傳列表圖片',
            'sdgs.max' => 'SDGs 標籤最多只能選擇 5 個',
        ];
    }
}
