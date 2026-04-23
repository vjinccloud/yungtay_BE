<?php

namespace Modules\RegisterBonus\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class RegisterBonusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'is_active' => ['required', 'boolean'],
            'bonus_amount' => ['required', 'integer', 'min:0'],
            'expiry_type' => ['required', 'in:unlimited,days'],
        ];

        if ($this->input('expiry_type') === 'days') {
            $rules['expiry_days'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'bonus_amount.required' => '請輸入贈送數值',
            'bonus_amount.integer' => '贈送數值必須是整數',
            'bonus_amount.min' => '贈送數值不能小於 0',
            'expiry_type.required' => '請選擇有效期限',
            'expiry_type.in' => '有效期限格式不正確',
            'expiry_days.required' => '請輸入有效天數',
            'expiry_days.integer' => '有效天數必須是整數',
            'expiry_days.min' => '有效天數至少為 1 天',
        ];
    }
}
