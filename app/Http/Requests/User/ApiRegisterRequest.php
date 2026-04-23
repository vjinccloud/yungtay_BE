<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ApiRegisterRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'account' => [
                'required',
                'email',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'max:16',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)[\w\W]{6,16}$/'
            ],
            'passwordVerify' => [
                'required',
                'same:password'
            ],
            'user' => [
                'required',
                'string',
                'min:2',
                'max:50'
            ],
            'birthday' => [
                'required',
                'date'
            ],
            'placeResidence' => [
                'required',
                'integer',
                'exists:list_city,sn'
            ],
            'sex' => [
                'required',
                'in:male,female'
            ],
            'agree_terms' => [
                'required',
                'accepted'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'account.required' => __('validation.required'),
            'account.email' => __('validation.email'),
            'account.unique' => __('validation.unique'),
            
            'password.required' => __('validation.required'),
            'password.min' => __('validation.min.string'),
            'password.max' => __('validation.max.string'),
            'password.regex' => __('validation.password.regex'),
            
            'passwordVerify.required' => __('validation.required'),
            'passwordVerify.same' => __('validation.confirmed'),
            
            'user.required' => __('validation.required'),
            'user.min' => __('validation.min.string'),
            'user.max' => __('validation.max.string'),
            
            'birthday.required' => __('validation.required'),
            'birthday.date' => __('validation.date'),
            
            'placeResidence.required' => __('validation.required'),
            'placeResidence.integer' => __('validation.integer'),
            'placeResidence.exists' => __('validation.exists'),
            
            'sex.required' => __('validation.required'),
            'sex.in' => __('validation.in'),
            
            'agree_terms.required' => __('validation.required'),
            'agree_terms.accepted' => __('validation.required')
        ];
    }

    /**
     * Get the validated data with field transformation.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // 欄位轉換：前台欄位名 → 後台欄位名
        $transformed = [
            'email' => $validated['account'],           // account → email
            'name' => $validated['user'],               // user → name
            'password' => $validated['password'],       // 保持不變
            'birthdate' => $validated['birthday'],      // birthday → birthdate
            'city_id' => $validated['placeResidence'],  // placeResidence → city_id
            'gender' => $validated['sex']               // sex → gender
        ];
        
        return $transformed;
    }
}