<?php

namespace Modules\HomeImageSetting\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SlimImageRule;
use Modules\HomeImageSetting\Model\HomeImageSetting;

/**
 * HomeImageSetting 首頁圖片設定 - Request
 */
class HomeImageSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 取得現有設定
        $record = HomeImageSetting::with(['imageZh', 'imageEn'])->first();

        // 讀取前端是否清除過圖片
        $imageZhCleared = $this->boolean('slimImageZhCleared');
        $imageEnCleared = $this->boolean('slimImageEnCleared');

        // 判斷舊圖是否存在
        $hasImageZh = $record && !$imageZhCleared && optional($record->imageZh)->exists;
        $hasImageEn = $record && !$imageEnCleared && optional($record->imageEn)->exists;

        return [
            // 標題驗證
            'title.zh_TW' => ['required', 'string', 'max:100'],
            'title.en' => ['required', 'string', 'max:100'],

            // 中文版圖片
            'slimImageZh' => array_filter([
                $imageZhCleared ? 'required' : 'sometimes',
                new SlimImageRule(
                    required: true,
                    maxSizeMB: 5,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    hasOriginalImage: $hasImageZh
                ),
            ]),

            // 英文版圖片
            'slimImageEn' => array_filter([
                $imageEnCleared ? 'required' : 'sometimes',
                new SlimImageRule(
                    required: true,
                    maxSizeMB: 5,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    hasOriginalImage: $hasImageEn
                ),
            ]),
        ];
    }

    public function messages(): array
    {
        return [
            'title.zh_TW.required' => '請輸入中文標題',
            'title.en.required' => '請輸入英文標題',
            'slimImageZh.required' => '請上傳中文版圖片',
            'slimImageEn.required' => '請上傳英文版圖片',
        ];
    }
}
