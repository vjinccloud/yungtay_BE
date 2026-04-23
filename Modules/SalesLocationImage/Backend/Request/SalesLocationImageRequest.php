<?php

namespace Modules\SalesLocationImage\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class SalesLocationImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_zh' => 'nullable|string',
            'image_en' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'image_zh' => '中文圖片',
            'image_en' => '英文圖片',
        ];
    }
}
