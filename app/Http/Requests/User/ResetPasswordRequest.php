<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ResetPasswordRequest extends FormRequest
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
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:6',
                'max:16',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)[\w\W]{6,16}$/',
                'confirmed'
            ],
            'password_confirmation' => 'required'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->password) {
                $user = User::where('email', $this->email)->first();
                if ($user && Hash::check($this->password, $user->password)) {
                    $validator->errors()->add('password', __('validation.password.same_as_current'));
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'token.required' => __('validation.required'),
            'email.required' => __('validation.required'),
            'email.email' => __('validation.email'),
            'password.required' => __('validation.required'),
            'password.min' => __('validation.password.regex'),
            'password.max' => __('validation.password.regex'),
            'password.regex' => __('validation.password.regex'),
            'password.confirmed' => __('validation.confirmed'),
            'password_confirmation.required' => __('validation.required')
        ];
    }
}