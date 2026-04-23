<?php

namespace App\Http\Requests\RadioTheme;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RadioThemeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $themeId = $this->route('id');
        $isUpdate = ! is_null($themeId);
        return [
            'name' => 'required|array',
            'name.zh_TW' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'radio_id' => [
                'required',
                'integer',
                'exists:radios,id',
                 // 條件唯一：在同一 theme_id 下 radio_id 不可重複
                 $isUpdate
                    ? Rule::unique('radio_theme_relations', 'radio_id')
                          ->where(fn($q) => $q->where('theme_id', $themeId))
                          ->ignore($this->input('relation_id'), 'id')
                    : null,
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'radio_id.unique' => '此廣播已在該主題中，請選擇其他廣播',
            'name.required' => '主題名稱為必填',
            'name.zh_TW.required' => '中文名稱為必填',
            'name.zh_TW.max' => '中文名稱最多 255 個字',
            'name.en.required' => '英文名稱為必填',
            'name.en.max' => '英文名稱最多 255 個字',
            'radio_id.required' => '請選擇廣播',
            'radio_id.exists' => '選擇的廣播不存在'
        ];
    }

}