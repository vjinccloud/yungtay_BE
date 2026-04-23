<?php

namespace Modules\GiftActivitySetting\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class GiftActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title'          => ['required', 'string', 'max:255'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after_or_equal:start_date'],
            'status'         => ['required', 'in:active,draft'],
            'condition_type' => ['required', 'in:all,order_total,category'],
            'gift_products'              => ['required', 'array', 'min:1'],
            'gift_products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'gift_products.*.sku_id'     => ['nullable', 'integer'],
            'gift_products.*.qty'        => ['required', 'integer', 'min:1'],
        ];

        if ($this->input('condition_type') === 'order_total') {
            $rules['condition_amount'] = ['required', 'integer', 'min:1'];
        }

        if ($this->input('condition_type') === 'category') {
            $rules['condition_category_ids']   = ['required', 'array', 'min:1'];
            $rules['condition_category_ids.*'] = ['integer'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required'                  => '請輸入活動名稱',
            'start_date.required'             => '請選擇開始日期',
            'end_date.required'               => '請選擇結束日期',
            'end_date.after_or_equal'         => '結束日期不能早於開始日期',
            'condition_amount.required'       => '請輸入滿足金額',
            'condition_amount.min'            => '滿足金額至少為 1',
            'condition_category_ids.required' => '請選擇至少一個商品分類',
            'gift_products.required'          => '請選擇至少一個贈品',
            'gift_products.min'               => '請選擇至少一個贈品',
        ];
    }
}
