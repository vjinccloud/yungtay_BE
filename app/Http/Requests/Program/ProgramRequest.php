<?php

namespace App\Http\Requests\Program;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SlimImageRule;
use App\Models\Program;

class ProgramRequest extends FormRequest
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
        // 1. 看是不是編輯
        $id       = $this->route('id');
        $isUpdate = ! is_null($id);

        // 2. 載入 model 並 preload 關聯
        $program = $isUpdate
            ? Program::with(['posterDesktop','posterMobile', 'bannerDesktop', 'bannerMobile'])->find($id)
            : null;

        // 3. 讀前端是否清除過
        $desktopCleared = $this->boolean('slimClearedDesktop');
        $mobileCleared  = $this->boolean('slimClearedMobile');
        $bannerDesktopCleared = $this->boolean('slimClearedBannerDesktop');
        $bannerMobileCleared = $this->boolean('slimClearedBannerMobile');
        
        // 4. 判斷舊圖是否真的還在
        $hasDesktop = $isUpdate
            && ! $desktopCleared
            && optional($program->posterDesktop)->exists;
        $hasMobile  = $isUpdate
            && ! $mobileCleared
            && optional($program->posterMobile)->exists;
        $hasBannerDesktop = $isUpdate
            && ! $bannerDesktopCleared
            && optional($program->bannerDesktop)->exists;
        $hasBannerMobile = $isUpdate
            && ! $bannerMobileCleared
            && optional($program->bannerMobile)->exists;

        return [
            // 多語系標題
            'title.zh_TW' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],

            // 多語系描述
            'description.zh_TW' => ['required', 'string'],
            'description.en' => ['required', 'string'],

            // 多語系製作團隊
            'crew.zh_TW' => ['required', 'string'],
            'crew.en' => ['required', 'string'],

            // 多語系標籤
            'tags.zh_TW' => ['required', 'string'],
            'tags.en' => ['required', 'string'],

            // 多語系其他資訊
            'other_info.zh_TW' => ['required', 'string'],
            'other_info.en' => ['required', 'string'],

            // 分類
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id'],

            // 季數和年份
            'season_number' => ['required', 'integer', 'min:1', 'max:20'],
            'release_year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:' . (date('Y') + 5)
            ],

            // 發佈日期
            'published_date' => ['nullable', 'date'],

            // 啟用狀態
            'is_active' => ['boolean'],
            
            // ─── 桌機版海報 ──────────────────────
            'poster_desktop' => array_filter([
                // 編輯時沒清除 => sometimes，否則新增一定要傳
                $isUpdate
                    ? ($desktopCleared ? 'required' : 'sometimes')
                    : 'required',

                new SlimImageRule(
                    required:         true,
                    maxSizeMB:        8,
                    allowedTypes:     ['image/jpeg','image/png','image/webp'],
                    hasOriginalImage: $hasDesktop  // true，空值可略過
                ),
            ]),

            // ─── 手機版海報 ──────────────────────
            'poster_mobile' => array_filter([
                // 因為 mobileCleared===true，編輯時也要 required
                $isUpdate
                    ? ($mobileCleared  ? 'required' : 'sometimes')
                    : 'required',

                new SlimImageRule(
                    required:         true,
                    maxSizeMB:        5,
                    allowedTypes:     ['image/jpeg','image/png','image/webp'],
                    hasOriginalImage: $hasMobile   // false，不跳過
                ),
            ]),

            // ─── 影片Banner桌機版 ──────────────────────
            'banner_desktop' => array_filter([
                // 和海報一樣的邏輯：編輯時沒清除 => sometimes，否則新增一定要傳
                $isUpdate
                    ? ($bannerDesktopCleared ? 'required' : 'sometimes')
                    : 'required',

                new SlimImageRule(
                    required:         true,
                    maxSizeMB:        10,
                    allowedTypes:     ['image/jpeg','image/png','image/webp'],
                    hasOriginalImage: $hasBannerDesktop
                ),
            ]),

            // ─── 影片Banner手機版 ──────────────────────
            'banner_mobile' => array_filter([
                // 和海報一樣的邏輯：編輯時沒清除 => sometimes，否則新增一定要傳
                $isUpdate
                    ? ($bannerMobileCleared ? 'required' : 'sometimes')
                    : 'required',

                new SlimImageRule(
                    required:         true,
                    maxSizeMB:        8,
                    allowedTypes:     ['image/jpeg','image/png','image/webp'],
                    hasOriginalImage: $hasBannerMobile
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
            'title.zh_TW.required' => '中文標題為必填欄位',
            'title.zh_TW.max' => '中文標題不得超過 255 個字元',
            'title.en.required' => '英文標題為必填欄位',
            'title.en.max' => '英文標題不得超過 255 個字元',

            'description.zh_TW.required' => '中文描述為必填欄位',
            'description.en.required' => '英文描述為必填欄位',
            'crew.zh_TW.required' => '中文製作團隊為必填欄位',
            'crew.en.required' => '英文製作團隊為必填欄位',
            'tags.zh_TW.required' => '中文標籤為必填欄位',
            'tags.en.required' => '英文標籤為必填欄位',
            'other_info.zh_TW.required' => '中文其他資訊為必填欄位',
            'other_info.en.required' => '英文其他資訊為必填欄位',

            'category_id.required' => '請選擇主分類',
            'category_id.exists' => '選擇的主分類不存在',
            'subcategory_id.required' => '請選擇子分類',
            'subcategory_id.exists' => '選擇的子分類不存在',

            'season_number.required' => '請選擇季數',
            'season_number.min' => '季數不能小於 1',
            'season_number.max' => '季數不能大於 20',

            'release_year.required' => '發行年份為必填欄位',
            'release_year.min' => '發行年份不能早於 1900 年',
            'release_year.max' => '發行年份不能晚於 ' . (date('Y') + 5) . ' 年',

            'published_date.required' => '發佈日期為必填欄位',
            'published_date.date' => '發佈日期格式不正確',

            'poster_desktop.required' => '桌機版圖片為必填欄位',
            'poster_mobile.required' => '手機版圖片為必填欄位',
            'banner_desktop.required' => '影片Banner桌機版為必填欄位',
            'banner_mobile.required' => '影片Banner手機版為必填欄位',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title.zh_TW' => '中文標題',
            'title.en' => '英文標題',
            'description.zh_TW' => '中文描述',
            'description.en' => '英文描述',
            'crew.zh_TW' => '中文製作團隊',
            'crew.en' => '英文製作團隊',
            'tags.zh_TW' => '中文標籤',
            'tags.en' => '英文標籤',
            'other_info.zh_TW' => '中文其他資訊',
            'other_info.en' => '英文其他資訊',
            'category_id' => '主分類',
            'subcategory_id' => '子分類',
            'season_number' => '季數',
            'release_year' => '發行年份',
            'published_date' => '發佈日期',
            'is_active' => '啟用狀態',
            'poster_desktop' => '桌機版海報',
            'poster_mobile' => '手機版海報',
            'banner_desktop' => '影片Banner桌機版',
            'banner_mobile' => '影片Banner手機版',
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

        // 如果沒有傳遞 is_active，預設為 false
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => false]);
        }

        // 處理空字串轉 null（現在大部分都是必填，只保留少數欄位）
        $nullableFields = [
            // 大部分欄位都改為必填，這裡只保留真正可為空的欄位
        ];

        $data = $this->all();

        foreach ($nullableFields as $field) {
            if (str_contains($field, '.')) {
                // 處理巢狀欄位
                $keys = explode('.', $field);
                if (isset($data[$keys[0]][$keys[1]]) && $data[$keys[0]][$keys[1]] === '') {
                    $data[$keys[0]][$keys[1]] = null;
                }
            } else {
                // 處理一般欄位
                if (isset($data[$field]) && $data[$field] === '') {
                    $data[$field] = null;
                }
            }
        }

        $this->replace($data);
    }
}