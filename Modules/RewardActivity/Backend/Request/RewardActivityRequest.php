<?php

namespace Modules\RewardActivity\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class RewardActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:active,draft'],
            'show_on_frontend' => ['required', 'boolean'],
            'promo_code' => ['required', 'string', 'max:100'],
            'condition_type' => ['required', 'in:all,order_total,category'],
            'reward_type' => ['required', 'in:shopping_credit,percentage_discount'],
            'reward_value' => ['required', 'integer', 'min:0'],
            'redemption_limit_type' => ['required', 'in:unlimited,once_per_member,site_total'],
        ];

        if ($this->input('condition_type') === 'order_total') {
            $rules['condition_order_total'] = ['required', 'integer', 'min:1'];
        }

        if ($this->input('condition_type') === 'category') {
            $rules['condition_category_ids'] = ['required', 'array', 'min:1'];
            $rules['condition_category_ids.*'] = ['integer'];
        }

        if ($this->input('reward_type') === 'percentage_discount') {
            $rules['reward_value'] = ['required', 'integer', 'min:1', 'max:100'];
        }

        if ($this->input('reward_type') === 'shopping_credit') {
            $rules['credit_expiry_type'] = ['required', 'in:unlimited,days'];
        }

        if ($this->input('credit_expiry_type') === 'days') {
            $rules['credit_expiry_days'] = ['required', 'integer', 'min:1'];
        }

        if ($this->input('redemption_limit_type') === 'site_total') {
            $rules['redemption_site_total'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => '請輸入活動標題',
            'start_date.required' => '請選擇開始日期',
            'end_date.required' => '請選擇結束日期',
            'end_date.after_or_equal' => '結束日期不能早於開始日期',
            'description.required' => '請輸入活動描述',
            'promo_code.required' => '請輸入優惠代碼',
            'reward_value.required' => '請輸入獎勵數值',
            'reward_value.min' => '獎勵數值不能小於 0',
            'reward_value.max' => '百分比折扣不能超過 100%',
            'condition_order_total.required' => '請輸入全單達到金額',
            'condition_order_total.min' => '金額至少為 1',
            'condition_category_ids.required' => '請選擇至少一個分類',
            'credit_expiry_days.required' => '請輸入有效天數',
            'credit_expiry_days.min' => '有效天數至少為 1 天',
            'redemption_site_total.required' => '請輸入全站使用次數',
            'redemption_site_total.min' => '次數至少為 1',
        ];
    }
}
