<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SlimImageRule;
use App\Models\Banner;

class BannerRequest extends FormRequest
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
        // 1. 判斷是否為編輯模式
        $id = $this->route('id') ?? $this->route('banner');
        $isUpdate = !is_null($id);

        // 2. 載入 model 並預載關聯
        $banner = $isUpdate 
            ? Banner::with(['desktopImage', 'mobileImage'])->find($id) 
            : null;

        // 3. 讀取前端是否清除過圖片
        $desktopCleared = $this->boolean('slimDesktopCleared');
        $mobileCleared = $this->boolean('slimMobileCleared');

        // 4. 判斷舊圖是否真的還存在
        $hasDesktop = $isUpdate 
            && !$desktopCleared 
            && optional($banner->desktopImage)->exists;
        
        $hasMobile = $isUpdate 
            && !$mobileCleared 
            && optional($banner->mobileImage)->exists;

        return [
            // 標題（僅中文，暫時隱藏欄位，設為選填）
            'title.zh_TW' => ['nullable', 'string', 'max:100'],

            // 簡述1（蓋字區塊）（僅中文，暫時隱藏欄位，設為選填）
            'subtitle_1.zh_TW' => ['nullable', 'string', 'max:200'],

            // 簡述2（僅中文，暫時隱藏欄位，設為選填）
            'subtitle_2.zh_TW' => ['nullable', 'string', 'max:500'],

            // 連結網址（單一欄位，非多語系，選填）
            'url' => ['nullable', 'url', 'max:255'],

            // 標籤（僅中文，選填）
            'tags.zh_TW' => ['nullable', 'string'],

            // 啟用狀態
            'is_active' => ['boolean'],

            // 排序欄位不需要驗證，Repository 會自動處理

            // 桌機版圖片驗證
            'slimDesktop' => array_filter([
                // 編輯時：如果清除了就必填，否則選填
                // 新增時：必填
                $isUpdate 
                    ? ($desktopCleared ? 'required' : 'sometimes')
                    : 'required',
                
                new SlimImageRule(
                    required: true,
                    maxSizeMB: 8,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    hasOriginalImage: $hasDesktop
                ),
            ]),

            // 手機版圖片驗證
            'slimMobile' => array_filter([
                // 編輯時：如果清除了就必填，否則選填
                // 新增時：必填
                $isUpdate 
                    ? ($mobileCleared ? 'required' : 'sometimes')
                    : 'required',
                
                new SlimImageRule(
                    required: true,
                    maxSizeMB: 5,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    hasOriginalImage: $hasMobile
                ),
            ]),
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.zh_TW.max' => '標題不得超過 100 個字元',

            'subtitle_1.zh_TW.max' => '簡述1不得超過 200 個字元',

            'subtitle_2.zh_TW.max' => '簡述2不得超過 500 個字元',

            'url.url' => '請輸入有效的網址格式',
            'url.max' => '連結網址不得超過 255 個字元',

            'slimDesktop.required' => '桌機版圖片為必填欄位',
            'slimMobile.required' => '手機版圖片為必填欄位',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title.zh_TW' => '標題',
            'subtitle_1.zh_TW' => '簡述1',
            'subtitle_2.zh_TW' => '簡述2',
            'url' => '連結網址',
            'tags.zh_TW' => '標籤',
            'is_active' => '啟用狀態',
            'slimDesktop' => '桌機版圖片',
            'slimMobile' => '手機版圖片',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // 處理 checkbox 的 boolean 值
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active')
            ]);
        }

        // 如果沒有傳遞 is_active，預設為 true（預設啟用）
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}