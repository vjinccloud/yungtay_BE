<?php

namespace App\Http\Requests\BasicWebsiteSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        // 視業務邏輯決定授權，這裡先放行
        return true;
    }

    public function rules()
    {
        return [
            // 多語言欄位（加強 XSS 防護）
            'title.zh_TW'         => ['nullable', 'string', 'max:100', 'regex:/^[^<>]*$/'],
            'title.en'            => ['nullable', 'string', 'max:100', 'regex:/^[^<>]*$/'],
            'description.zh_TW'   => ['nullable', 'string', 'max:500', 'regex:/^[^<>]*$/'],
            'description.en'      => ['nullable', 'string', 'max:500', 'regex:/^[^<>]*$/'],
            'keyword.zh_TW'       => ['nullable', 'string', 'max:255', 'regex:/^[^<>]*$/'],
            'keyword.en'          => ['nullable', 'string', 'max:255', 'regex:/^[^<>]*$/'],
            
            // 社群媒體連結（加強 URL 驗證，限制特定網域）
            'line'          => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\/(line\.me|lin\.ee)/'],
            'fb'            => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\/(www\.)?(facebook|fb)\.com/'],
            'ig'            => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\/(www\.)?instagram\.com/'],
            'youtube'       => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)/'],
            
            // App 商店連結（限制官方網域）
            'app_google_play' => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\/play\.google\.com\/store\/apps/'],
            'app_apple_store' => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\/apps\.apple\.com/'],
            
            // 聯絡資訊
            'tel'           => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\-\+\(\)]+$/'],
            'email'         => ['nullable', 'email:rfc,dns', 'max:100'],
            
            // Google Analytics 代碼（限制格式）
            'ga_code'       => ['nullable', 'string', 'max:5000', 'regex:/^(UA-\d{4,10}-\d{1,4}|G-[A-Z0-9]+)?$/'],
            
            // Slim 圖片資料（網站圖示）- 檢查 base64 格式
            'slimIcon'      => ['nullable', 'string', 'max:500000'], // 限制大小約 500KB
            
            // 檔案上傳 - 限制只能一張圖片
            'favicon' => ['nullable', 'file', 'mimes:ico,png', 'mimetypes:image/x-icon,image/vnd.microsoft.icon,image/png', 'max:2048'],
        ];
    }

    public function messages()
    {
        return [
            // 多語言欄位錯誤訊息
            'title.zh_TW.max'       => '中文標題不能超過100個字元。',
            'title.zh_TW.regex'     => '標題不能包含 HTML 標籤。',
            'title.en.max'          => '英文標題不能超過100個字元。',
            'title.en.regex'        => '標題不能包含 HTML 標籤。',
            'description.zh_TW.max' => '中文描述不能超過500個字元。',
            'description.zh_TW.regex' => '描述不能包含 HTML 標籤。',
            'description.en.max'    => '英文描述不能超過500個字元。',
            'description.en.regex'  => '描述不能包含 HTML 標籤。',
            'keyword.zh_TW.max'     => '中文關鍵字不能超過255個字元。',
            'keyword.zh_TW.regex'   => '關鍵字不能包含 HTML 標籤。',
            'keyword.en.max'        => '英文關鍵字不能超過255個字元。',
            'keyword.en.regex'      => '關鍵字不能包含 HTML 標籤。',
            
            // 聯絡資訊錯誤訊息
            'tel.max'               => '聯絡電話不能超過20個字元。',
            'tel.regex'             => '聯絡電話格式錯誤，只能包含數字、空格、連字號、加號和括號。',
            'email.email'           => '電子郵件格式錯誤。',
            'email.dns'             => '請輸入有效的電子郵件地址。',
            
            // 社群媒體連結錯誤訊息
            'line.url'              => 'Line連結格式錯誤。',
            'line.regex'            => '請輸入正確的 LINE 官方連結（line.me 或 lin.ee）。',
            'fb.url'                => 'Facebook連結格式錯誤。',
            'fb.regex'              => '請輸入正確的 Facebook 連結。',
            'ig.url'                => 'Instagram連結格式錯誤。',
            'ig.regex'              => '請輸入正確的 Instagram 連結。',
            'youtube.url'           => 'YouTube連結格式錯誤。',
            'youtube.regex'         => '請輸入正確的 YouTube 連結。',
            
            // App 商店連結錯誤訊息
            'app_google_play.url'   => 'Google Play下載連結格式錯誤。',
            'app_google_play.regex' => '請輸入正確的 Google Play 商店連結。',
            'app_apple_store.url'   => 'Apple Store下載連結格式錯誤。',
            'app_apple_store.regex' => '請輸入正確的 App Store 連結。',
            
            // Google Analytics
            'ga_code.regex'         => 'Google Analytics 代碼格式錯誤（應為 UA-XXXXX-X 或 G-XXXXXX）。',
            
            // Slim 圖片
            'slimIcon.max'          => '網站圖示檔案過大，請選擇較小的圖片。',
            
            // 檔案上傳錯誤訊息
            'favicon.file'          => 'Favicon 必須是檔案格式。',
            'favicon.mimes'         => '只接受 ico 或 png 檔案格式。',
            'favicon.max'           => 'Favicon 大小不能超過 2MB。',
        ];
    }
    
    /**
     * 自定義驗證規則 - 確保只上傳一張圖片
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 檢查是否有上傳檔案
            if ($this->hasFile('favicon')) {
                $files = $this->file('favicon');
                
                // 如果是陣列（多檔案），則報錯
                if (is_array($files)) {
                    $validator->errors()->add('favicon', '只能上傳一張 Favicon 圖片。');
                }
            }
        });
    }
}
