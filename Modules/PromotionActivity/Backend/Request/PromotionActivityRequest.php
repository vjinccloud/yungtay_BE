<?php

namespace Modules\PromotionActivity\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class PromotionActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'min_amount' => ['required', 'integer', 'min:0'],
            'discount_amount' => ['required', 'integer', 'min:0'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '請輸入活動標題',
            'title.max' => '標題最多 255 個字',
            'is_active.required' => '請設定啟用狀態',
            'start_date.required' => '請選擇活動開始日期',
            'start_date.date' => '開始日期格式不正確',
            'end_date.required' => '請選擇活動結束日期',
            'end_date.date' => '結束日期格式不正確',
            'end_date.after_or_equal' => '結束日期不能早於開始日期',
            'min_amount.required' => '請輸入滿額金額',
            'min_amount.integer' => '滿額金額必須是整數',
            'min_amount.min' => '滿額金額不能小於 0',
            'discount_amount.required' => '請輸入抵扣金額',
            'discount_amount.integer' => '抵扣金額必須是整數',
            'discount_amount.min' => '抵扣金額不能小於 0',
        ];
    }
}
