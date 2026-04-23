<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MailRecipientRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $rules = [
            'type_id' => ['required', 'integer', 'exists:mail_types,id'],
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'status' => ['sometimes', 'boolean'],
        ];

        // 更新時需要排除當前記錄的 email 重複檢查
        if ($this->route('id')) {
            $rules['email'][] = Rule::unique('mail_recipients', 'email')->ignore($this->route('id'));
        } else {
            $rules['email'][] = 'unique:mail_recipients,email';
        }

        return $rules;
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'type_id.required' => '請選擇收件類型',
            'type_id.exists' => '選擇的收件類型不存在',
            'name.required' => '請輸入收信人名稱',
            'name.min' => '收信人名稱至少需要 2 個字元',
            'name.max' => '收信人名稱不能超過 50 個字元',
            'email.required' => '請輸入信箱地址',
            'email.email' => '信箱格式不正確',
            'email.max' => '信箱地址不能超過 100 個字元',
            'email.unique' => '此信箱地址已經存在',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     */
    public function attributes(): array
    {
        return [
            'type_id' => '收件類型',
            'name' => '收信人名稱',
            'email' => '信箱地址',
            'status' => '狀態',
        ];
    }
}