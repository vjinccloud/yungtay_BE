<?php

namespace App\Http\Requests\Radio;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SlimImageRule;
use App\Models\Radio;

class RadioRequest extends FormRequest
{
    protected ?Radio $model = null;
    
    public function prepareForValidation()
    {
        // 從 route 參數拿 ID
        $id = $this->route('id') ?? $this->route('radio'); // 看你 route 是 /radios/{id} 還是用 model 綁定

        if ($id) {
            $this->model = Radio::find($id);
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
            'title.zh_TW'        => 'required|string|max:255',
            'title.en'           => 'required|string|max:255',
            'description.zh_TW'  => 'nullable|string|max:5000',
            'description.en'     => 'nullable|string|max:5000',
            'media_name.zh_TW'   => 'required|string|max:255',
            'media_name.en'      => 'required|string|max:255',
            'category_id'        => 'required|exists:categories,id',
            'subcategory_id'     => 'nullable|exists:categories,id',
            'year'               => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'season'             => 'required|integer|min:1|max:7',
            'publish_date'       => 'required|date',
            'is_active'          => 'boolean',
            'slim'               => [new SlimImageRule(
                                     required: false, // 廣播封面圖片為選填
                                     allowedTypes: ['image/jpeg', 'image/png'],
                                     hasOriginalImage: $hasOriginalImage
                                 )],
            'banner_desktop'     => [new SlimImageRule(
                                     required: false,
                                     allowedTypes: ['image/jpeg', 'image/png'],
                                     hasOriginalImage: false
                                 )],
            'banner_mobile'      => [new SlimImageRule(
                                     required: false,
                                     allowedTypes: ['image/jpeg', 'image/png'],
                                     hasOriginalImage: false
                                 )],
        ];
    }

    public function messages(): array
    {
        return [
            'title.zh_TW.required'      => '請輸入中文標題',
            'title.en.required'         => '請輸入英文標題',
            'description.zh_TW.max'     => '中文簡介不能超過5000字',
            'description.en.max'        => '英文簡介不能超過5000字',
            'media_name.zh_TW.required' => '請輸入中文媒體名稱',
            'media_name.en.required'    => '請輸入英文媒體名稱',
            'category_id.required'      => '請選擇分類',
            'category_id.exists'        => '選擇的分類不存在',
            'subcategory_id.exists'     => '選擇的子分類不存在',
            'year.required'             => '請輸入年份',
            'year.integer'              => '年份必須是整數',
            'year.min'                  => '年份不能小於1900',
            'year.max'                  => '年份不能超過' . (date('Y') + 1),
            'audio_url.required'        => '請上傳音訊檔案',
            'publish_date.required'     => '請選擇發布日期',
            'publish_date.date'         => '發布日期格式不正確',
        ];
    }
}