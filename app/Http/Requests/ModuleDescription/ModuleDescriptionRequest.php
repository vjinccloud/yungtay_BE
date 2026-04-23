<?php
// app/Http/Requests/ModuleDescription/ModuleDescriptionRequest.php

namespace App\Http\Requests\ModuleDescription;

use Illuminate\Foundation\Http\FormRequest;

class ModuleDescriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 模組類型
            'module_key' => 'required|string|unique:module_descriptions,module_key,' . ($this->route('id') ?? 'NULL'),
            
            // SEO描述（僅中文，必填）
            'meta_description' => 'required|array',
            'meta_description.zh_TW' => 'required|string',
            
            // SEO關鍵字（僅中文，選填 - 預留未來使用）
            'meta_keywords' => 'nullable|array',
            'meta_keywords.zh_TW' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'module_key.required' => '模組類型為必填欄位',
            'module_key.unique' => '此模組類型已存在',
            
            'meta_description.required' => 'SEO描述為必填欄位',
            'meta_description.array' => 'SEO描述格式錯誤',
            'meta_description.zh_TW.required' => 'SEO描述為必填欄位',
            'meta_description.zh_TW.string' => 'SEO描述必須為字串',
            
            'meta_keywords.array' => 'SEO關鍵字格式錯誤',
            'meta_keywords.zh_TW.string' => 'SEO關鍵字必須為字串',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'module_key' => '模組類型',
            'meta_description' => 'SEO描述',
            'meta_description.zh_TW' => 'SEO描述',
            'meta_keywords' => 'SEO關鍵字',
            'meta_keywords.zh_TW' => 'SEO關鍵字',
        ];
    }

}