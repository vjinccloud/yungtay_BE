<?php

namespace App\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SlimImageRule;
use App\Models\Article;

class ArticleRequest extends FormRequest
{
    protected ?Article $model = null;

    public function prepareForValidation()
    {
        // 從 route 參數拿 ID
        $id = $this->route('id') ?? $this->route('article'); // 看你 route 是 /articles/{id} 還是用 model 綁定

        if ($id) {
            $this->model = Article::find($id);
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
            'category_id'       => 'required|integer|exists:categories,id',
            'title.zh_TW'       => 'required|string|max:255',
            'title.en'          => 'required|string|max:255',
            'author.zh_TW'      => 'required|string|max:255',
            'author.en'         => 'required|string|max:255',
            'location.zh_TW'    => 'nullable|string|max:255',
            'location.en'       => 'nullable|string|max:255',
            'content.zh_TW'     => 'required|string',
            'content.en'        => 'required|string',
            'tags.zh_TW'        => 'required|string',
            'tags.en'           => 'required|string',
            'publish_date'      => 'required|date',
            'is_active'         => 'sometimes|boolean',
            'slim'              => [new SlimImageRule(
                                    required: false,
                                    allowedTypes: ['image/jpeg', 'image/png'],
                                    hasOriginalImage: $hasOriginalImage
                                )],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required'      => '請選擇新聞分類',
            'category_id.exists'        => '選擇的分類不存在',
            'title.zh_TW.required'      => '請輸入中文標題',
            'title.en.required'         => '請輸入英文標題',
            'author.zh_TW.required'     => '請輸入中文作者',
            'author.en.required'        => '請輸入英文作者',
            'content.zh_TW.required'    => '請輸入中文內容',
            'content.en.required'       => '請輸入英文內容',
            'tags.zh_TW.required'       => '請輸入中文標籤',
            'tags.en.required'          => '請輸入英文標籤',
            'publish_date.required'     => '請選擇上架日期',
            'publish_date.date'         => '日期格式不正確',
        ];
    }
}