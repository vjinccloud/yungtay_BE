<?php

namespace App\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;

class CollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content_type' => [
                'required',
                'string',
                'in:articles,drama,program,radio' // 不包含 live
            ],
            'content_id' => [
                'required',
                'integer',
                'min:1'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content_type.required' => '內容類型為必填',
            'content_type.in' => '內容類型無效',
            'content_id.required' => '內容ID為必填',
            'content_id.integer' => '內容ID必須為數字',
            'content_id.min' => '內容ID必須大於0',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // XSS 防護：清理輸入資料
        if ($this->has('content_type')) {
            $this->merge([
                'content_type' => strip_tags($this->content_type)
            ]);
        }
        
        if ($this->has('content_id')) {
            $this->merge([
                'content_id' => (int) $this->content_id
            ]);
        }
    }
}