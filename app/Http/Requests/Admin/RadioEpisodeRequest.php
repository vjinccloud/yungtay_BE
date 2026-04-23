<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RadioEpisodeRequest extends FormRequest
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
        $rules = [
            // radio_id 允許為 null 或 0（新增廣播時的暫存狀態）
            'radio_id' => 'nullable|integer|min:0',
            'season' => 'required|integer|min:1|max:7',
            'episode_number' => 'required|integer|min:1',
            'duration_text.zh_TW' => 'required|string|max:100',
            'duration_text.en' => 'required|string|max:100',
            'description.zh_TW' => 'nullable|string|max:1000',
            'description.en' => 'nullable|string|max:1000',
            'duration' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'audio_path' => 'nullable|string|max:500',
        ];

        // 新增時音檔路徑必填（由 AudioUploader 上傳後傳來）
        if ($this->isMethod('POST')) {
            $rules['audio_path'] = 'required|string|max:500';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'radio_id' => '廣播ID',
            'season' => '季數',
            'episode_number' => '集數編號',
            'title.zh_TW' => '中文標題',
            'title.en' => '英文標題',
            'duration_text.zh_TW' => '中文時長',
            'duration_text.en' => '英文時長',
            'description.zh_TW' => '中文簡介',
            'description.en' => '英文簡介',
            'audio_path' => '音檔路徑',
            'duration' => '時長',
            'is_active' => '啟用狀態',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'radio_id.integer' => '廣播ID必須是整數',
            'radio_id.min' => '廣播ID不可為負數',
            'season.required' => '請選擇季數',
            'season.integer' => '季數必須是整數',
            'season.min' => '季數最小為 1',
            'season.max' => '季數最大為 7',
            'episode_number.required' => '請輸入集數編號',
            'episode_number.integer' => '集數編號必須是整數',
            'episode_number.min' => '集數編號最小為 1',
            'title.zh_TW.required' => '請輸入中文標題',
            'title.zh_TW.max' => '中文標題不可超過 255 字元',
            'title.en.max' => '英文標題不可超過 255 字元',
            'duration_text.zh_TW.required' => '請輸入中文時長',
            'duration_text.zh_TW.max' => '中文時長不可超過 100 字元',
            'duration_text.en.required' => '請輸入英文時長',
            'duration_text.en.max' => '英文時長不可超過 100 字元',
            'description.zh_TW.max' => '中文簡介不可超過 1000 字元',
            'description.en.max' => '英文簡介不可超過 1000 字元',
            'audio_path.required' => '請上傳音檔',
            'audio_path.max' => '音檔路徑過長',
            'duration.integer' => '時長必須是整數',
            'duration.min' => '時長不可為負數',
        ];
    }
}
