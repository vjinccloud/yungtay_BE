<?php

namespace Modules\NewsManagement\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SlimImageRule;
use App\Models\News;

class NewsManagementRequest extends FormRequest
{
    protected ?News $model = null;

    public function prepareForValidation()
    {
        $id = $this->route('id');
        if ($id) {
            $this->model = News::find($id);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hasOriginalImage = $this->model?->image()->exists();
        $slimCleared = $this->boolean('slimCleared');
        $hasOriginalImage = $hasOriginalImage && !$slimCleared;

        return [
            'category_id'    => 'required|exists:categories,id',
            'title.zh_TW'    => 'required|string|max:255',
            'content.zh_TW'  => 'required|string',
            'description'    => 'nullable|string|max:500',
            'tags'           => 'nullable|string|max:255',
            'published_date' => 'required|date',
            'is_active'      => 'boolean',
            'slim'           => [new SlimImageRule(
                required: true,
                allowedTypes: ['image/jpeg', 'image/png'],
                hasOriginalImage: $hasOriginalImage
            )],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required'    => '請選擇分類',
            'title.zh_TW.required'    => '請輸入標題',
            'content.zh_TW.required'  => '請輸入內容',
            'published_date.required' => '請選擇上架日期',
            'published_date.date'     => '日期格式不正確',
        ];
    }
}
