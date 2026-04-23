<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: 視情況加上授權檢查（Policy/Gate）
        return true;
    }

    public function rules(): array
    {
        $categoryType = $this->input('type');

        // news 類型不需要英文欄位
        $typesWithoutEnglish = ['news'];

        $rules = [
            'type'                    => ['required', 'string'],
            'parent_name'             => ['required', 'array'],
            'parent_name.zh_TW'       => ['required', 'string', 'max:255'],
            'seq'                     => ['required', 'integer', 'min:0'],
            'status'                  => ['required', 'boolean'],
        ];

        // 只有非 news 類型才需要英文欄位
        if (!in_array($categoryType, $typesWithoutEnglish)) {
            $rules['parent_name.en'] = ['required', 'string', 'max:255'];
        } else {
            $rules['parent_name.en'] = ['nullable', 'string', 'max:255'];
        }

        // 根據分類類型決定子分類驗證規則
        $categoryType = $this->input('type');

        // 不允許子分類的類型
        $typesWithoutSubcategories = ['article', 'news'];

        // 子分類非必填的類型（允許子分類但可選）
        $typesWithOptionalSubcategories = ['radio'];

        if (in_array($categoryType, $typesWithoutSubcategories)) {
            // 完全不允許子分類（article）
            $rules['children'] = ['sometimes', 'array'];
            $rules['children.*.id'] = ['nullable', 'integer', 'exists:categories,id'];
            $rules['children.*.name'] = ['sometimes', 'array'];
            $rules['children.*.name.zh_TW'] = ['nullable', 'string', 'max:255'];
            $rules['children.*.name.en'] = ['nullable', 'string', 'max:255'];
            $rules['children.*.seq'] = ['sometimes', 'integer', 'min:0'];
        } elseif (in_array($categoryType, $typesWithOptionalSubcategories)) {
            // 允許子分類但非必填（radio）
            $rules['children'] = ['sometimes', 'array'];
            $rules['children.*.id'] = ['nullable', 'integer', 'exists:categories,id'];
            $rules['children.*.name'] = ['sometimes', 'array'];
            $rules['children.*.name.zh_TW'] = ['nullable', 'string', 'max:255'];
            $rules['children.*.name.en'] = ['nullable', 'string', 'max:255'];
            $rules['children.*.seq'] = ['sometimes', 'integer', 'min:0'];
        } else {
            // 子分類必填的類型（drama, program 等）
            $rules['children'] = ['required', 'array', 'min:1'];
            $rules['children.*.id'] = ['nullable', 'integer', 'exists:categories,id'];
            $rules['children.*.name'] = ['required', 'array'];
            $rules['children.*.name.zh_TW'] = ['required', 'string', 'max:255'];
            $rules['children.*.name.en'] = ['required', 'string', 'max:255'];
            $rules['children.*.seq'] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'parent_name.required'             => '主分類為必填',
            'parent_name.*.required'           => '主分類格式錯誤',
            'parent_name.zh-tw.required'       => '請輸入繁體主分類',
            'parent_name.en.required'          => '請輸入英文主分類',

            'children.required'                => '至少要有一個子分類',
            'children.*.name.zh-tw.required'   => '請輸入繁體子分類名稱',
            'children.*.name.en.required'      => '請輸入英文子分類名稱',
            'children.*.seq.required'          => '子分類排序為必填',

            'seq.required'                     => '排序(Parent Seq)為必填',
            'status.required'                  => '狀態為必填',
        ];
    }
}
