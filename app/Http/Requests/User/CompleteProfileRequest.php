<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255'
            ],
            'birthdate' => [
                'required',
                'date',
                'before:today'
            ],
            'gender' => [
                'required',
                'in:male,female'
            ],
            'address' => [
                'required',
                'string',
                'max:255',
                'not_in:""'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required'),
            'name.min' => __('validation.min.string'),
            'name.max' => __('validation.max.string'),
            
            'birthdate.required' => __('validation.required'),
            'birthdate.date' => __('validation.date'),
            'birthdate.before' => __('validation.before'),
            
            'gender.required' => __('validation.required'),
            'gender.in' => __('validation.in'),
            
            'address.required' => __('validation.required'),
            'address.max' => __('validation.max.string')
        ];
    }
}