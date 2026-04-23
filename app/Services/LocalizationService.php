<?php

namespace App\Services;

use Illuminate\Support\Facades\App;

class LocalizationService
{
    /**
     * 支援的語系
     */
    private array $supportedLocales = [
        'zh_TW' => '繁體中文',
        'en' => 'English'
    ];

    /**
     * 語系簡寫對應
     */
    private array $localeMapping = [
        'zh' => 'zh_TW',
        'zh_TW' => 'zh_TW',
        'en' => 'en'
    ];

    /**
     * 設定語系
     */
    public function setLocale(string $locale): void
    {
        $validatedLocale = $this->validateLocale($locale);
        
        App::setLocale($validatedLocale);
        session(['locale' => $validatedLocale]);
    }

    /**
     * 驗證語系，無效則回傳 zh_TW
     */
    public function validateLocale(string $locale): string
    {
        // 處理語系簡寫
        $locale = $this->localeMapping[$locale] ?? $locale;
        
        return array_key_exists($locale, $this->supportedLocales) ? $locale : 'zh_TW';
    }

    /**
     * 取得支援的語系陣列
     */
    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }
}