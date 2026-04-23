<?php

namespace App\Http\Requests\AdminUser;

use Illuminate\Foundation\Http\FormRequest;
use App\Exceptions\CustomPermissionException;

class EditProfileFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 定義驗證規則
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slim' => 'nullable|string', 
        ];
    
        // 如果密碼欄位有填寫，增加密碼相關的驗證
        if ($this->filled('password')) {
            $rules['password'] = [
                'required',
                'confirmed', // 確認字段必須與 password_confirmation 匹配
                'min:8',     // 至少 8 個字符
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}$/', // 必須包含大小寫字母和數字
            ];
            $rules['password_confirmation'] = [
                'required',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}$/', // 必須包含大小寫字母和數字
            ];
        }
    
        return $rules;
    }

}
