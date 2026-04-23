<?php

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SlimImageRule;
use App\Models\News;

class NewsRequest extends FormRequest
{
    protected ?News $model = null;
    public function prepareForValidation()
    {
        // 從 route 參數拿 ID
        $id = $this->route('id') ?? $this->route('news'); // 看你 route 是 /news/{id} 還是用 model 綁定

        if ($id) {
            $this->model = News::find($id);
        }
    }
    public function authorize(): bool
    {
        return true; // 根據實際權限需求可改為驗證授權邏輯
    }

    public function rules(): array
    {
        $hasOriginalImage = $this->model?->image()->exists();
        $slimCleared = $this->boolean('slimCleared');
        $hasOriginalImage = $hasOriginalImage && !$slimCleared;
        return [
            'category_id'   => 'required|exists:categories,id',
            'title.zh_TW'   => 'required|string|max:255',
            'title.en'      => 'nullable|string|max:255',
            'content.zh_TW' => 'required|string',
            'content.en'    => 'nullable|string',
            'description'   => 'nullable|string|max:500',
            'tags'          => 'nullable|string|max:255',
            'published_date'    => 'required|date',
            'is_active'     => 'boolean',
            'slim'          => [new SlimImageRule(
                required: true,
                allowedTypes: ['image/jpeg', 'image/png'],
                hasOriginalImage: $hasOriginalImage
            )],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => '請選擇分類',
            'title.zh_TW.required'   => '請輸入標題',
            'content.zh_TW.required' => '請輸入內容',
            'published_date.required'  => '請選擇日期',
            'published_date.date'      => '日期格式不正確',
        ];
    }
}
