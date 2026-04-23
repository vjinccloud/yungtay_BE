<?php

namespace Modules\ProductListing\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locales = config('translatable.locales', ['zh_TW' => []]);
        $primary = config('translatable.primary', 'zh_TW');

        $rules = [
            'name'           => 'required|array',
            'type'           => 'nullable|in:regular,gift',
            'status'         => 'required|in:0,1',
            'price'          => 'required|numeric|min:0',
            'stock'          => 'nullable|integer|min:0',
            'is_hot'         => 'nullable|boolean',
            'seq'            => 'nullable|integer|min:0',
            'front_menu_id'       => 'nullable|integer|exists:front_menus,id',
            'category_ids'        => 'nullable|array',
            'category_ids.*'      => 'integer|exists:front_menus,id',
            'spec_combination_id' => 'nullable|integer|exists:spec_combinations,id',
            'description'         => 'nullable|array',
            'main_image'          => 'nullable',
            'gallery_images'      => 'nullable|array',
            'skus'                => 'nullable|array',
            'skus.*.spec_value_ids'    => 'nullable|array',
            'skus.*.combination_label' => 'nullable|string',
            'skus.*.sku'               => 'nullable|string|max:100',
            'skus.*.price'             => 'nullable|numeric|min:0',
            'skus.*.stock'             => 'nullable|integer|min:0',
            'skus.*.status'            => 'nullable|boolean',
        ];

        // 根據 config 動態產生多語系驗證規則
        foreach ($locales as $locale => $cfg) {
            $rules["name.{$locale}"]        = ($locale === $primary ? 'required' : 'nullable') . '|string|max:255';
            $rules["description.{$locale}"] = 'nullable|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        $primary = config('translatable.primary', 'zh_TW');
        $label   = config("translatable.locales.{$primary}.label", '中文');

        return [
            'name.required'              => '請輸入商品名稱',
            "name.{$primary}.required"   => "請輸入商品名稱（{$label}）",
            'status.required'            => '請選擇狀態',
            'price.required'             => '請輸入售價',
            'price.min'                  => '售價不可為負數',
        ];
    }
}
