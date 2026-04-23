<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'agree_terms' => 'required|accepted'
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
            'name.required' => '請輸入姓名',
            'name.max' => '姓名不可超過 100 個字',
            'email.required' => '請輸入 Email',
            'email.email' => '請輸入有效的 Email 格式',
            'email.max' => 'Email 不可超過 255 個字',
            'phone.max' => '電話不可超過 50 個字',
            'address.max' => '地址不可超過 255 個字',
            'subject.required' => '請輸入主旨',
            'subject.max' => '主旨不可超過 255 個字',
            'message.required' => '請輸入內容',
            'message.max' => '內容不可超過 1000 個字',
            'agree_terms.required' => '請同意隱私權保護政策',
            'agree_terms.accepted' => '請同意隱私權保護政策'
        ];
    }
}