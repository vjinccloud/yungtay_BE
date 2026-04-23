<?php

namespace App\Repositories;

use App\Models\WebsiteInfo;
use App\Models\ImageManagement;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Cache;
use App\Services\UploadFileService as File;
use App\Repositories\ImageRepository;

class BasicWebsiteSettingRepository extends BaseRepository
{
    public function __construct(
        private WebsiteInfo $setting,
        private File $uploadFileService,
        private ImageRepository $imgRepository
    )
    {
        parent::__construct($setting,$uploadFileService);
    }


    public function getSettings($id = 1, bool $failIfNotFound = false)
    {
        // 使用快取，快取鍵為 website_settings_{id}
        $cacheKey = "website_settings_{$id}";
        
        // 如果需要拋出錯誤，則不使用快取（確保即時錯誤處理）
        if ($failIfNotFound) {
            Cache::forget($cacheKey); // 清除快取以確保取得最新資料
            $query = $this->model->newQuery();
            $setting = $query->findOrFail($id);
            return $this->formatSettingsData($setting);
        } else {
            // 使用快取，快取時間 24 小時
            return Cache::remember($cacheKey, now()->addHours(24), function () use ($id) {
                $query = $this->model->newQuery();
                $setting = $query->find($id);

                if (!$setting) {
                    return null;
                }

                return $this->formatSettingsData($setting);
            });
        }
    }

    /**
     * 格式化設定資料（統一處理邏輯）
     * 
     * @param \App\Models\BasicWebsiteSetting $setting
     * @return array
     */
    private function formatSettingsData($setting): array
    {
        return [
            'id'            => $setting->id,
            // 多語言欄位（直接使用 JSON 資料）
            'title'         => [
                'zh_TW' => $setting->getTranslations('title')['zh_TW'] ?? '',
                'en' => $setting->getTranslations('title')['en'] ?? '',
            ],
            'description'   => [
                'zh_TW' => $setting->getTranslations('description')['zh_TW'] ?? '',
                'en' => $setting->getTranslations('description')['en'] ?? '',
            ],
            'keyword'       => [
                'zh_TW' => $setting->getTranslations('keyword')['zh_TW'] ?? '',
                'en' => $setting->getTranslations('keyword')['en'] ?? '',
            ],
            // 單語言欄位
            'tel'           => $setting->tel,
            'fb'            => $setting->fb,
            'ig'            => $setting->ig,
            'line'          => $setting->line,
            'youtube'       => $setting->youtube,
            'app_google_play' => $setting->app_google_play,
            'app_apple_store' => $setting->app_apple_store,
            'email'         => $setting->email,
            'ga_code'       => $setting->ga_code,
            'favicon'       => $setting->favicon,
            'website_icon'  => $setting->websiteIcon ? url($setting->websiteIcon->path) : null,
        ];
    }

    /**
     * 儲存網站設定資料
     *
     * @param array $attributes 要儲存的屬性
     * @param int $id 網站設定 ID（預設為 1）
     * @return mixed
     */
    public function save(array $attributes, $id = 1)
    {
        // 1. 擷取並處理圖片資料
        $slimIconData = $this->extractSlimIconData($attributes);
        
        // 2. 處理 favicon 上傳
        $this->processFaviconUpload($attributes, $id);

        // 3. 儲存主資料
        $websiteInfo = parent::save($attributes, $id);

        // 4. 處理 Slim 網站圖示上傳
        if ($slimIconData) {
            $this->processWebsiteIconUpload($slimIconData, $websiteInfo);
        }

        // 5. 清除快取（網站設定已更新）
        $this->clearSettingsCache($id);

        return $websiteInfo;
    }

    /**
     * 從屬性中擷取 Slim 圖示資料
     *
     * @param array &$attributes 屬性陣列（會被修改）
     * @return mixed Slim 圖示資料或 null
     */
    private function extractSlimIconData(&$attributes)
    {
        $slimIconData = null;
        
        if (array_key_exists('slimIcon', $attributes)) {
            if (!is_null($attributes['slimIcon'])) {
                $slimIconData = $attributes['slimIcon'];
            }
            unset($attributes['slimIcon']); // 從主資料中移除，避免寫入到資料庫
        }
        
        return $slimIconData;
    }

    /**
     * 處理 favicon 檔案上傳
     *
     * @param array &$attributes 屬性陣列（會被修改）
     * @param int $id 網站設定 ID
     * @return void
     */
    private function processFaviconUpload(&$attributes, $id)
    {
        if (isset($attributes['favicon'])) {
            // 先取得 Model 實例來處理 favicon
            $websiteInfo = $this->model->findOrFail($id);
            
            $this->uploadFileService->saveFile(
                $attributes['favicon'],
                $websiteInfo,
                'icon',
                'icon'
            );
            
            unset($attributes['favicon']);  // 別讓它混進 DB 欄位
        }
    }

    /**
     * 處理網站圖示上傳（使用 Slim）
     *
     * @param mixed $slimIconData Slim 圖片資料
     * @param mixed $websiteInfo 網站資訊模型實例
     * @return void
     */
    private function processWebsiteIconUpload($slimIconData, $websiteInfo)
    {
        // 準備圖片資料格式（key 就是 image_type）
        $imageData = ['website_icon' => $slimIconData];

        // 取得舊圖片（使用 websiteIcon 關聯）
        $oldImage = $websiteInfo->websiteIcon ?? null;

        // 使用 ImageRepository 的 saveSlimFile 方法
        $this->imgRepository->saveSlimFile(
            $imageData,           // Slim 資料（key = image_type）
            $websiteInfo,        // Model 實例
            $oldImage,           // 舊圖片（如果有的話）
            'website'            // 儲存路徑
        );
    }

    /**
     * 取得前台專用的格式化資料
     *
     * @param string $locale 語系
     * @return array
     */
    public function getFrontendSettings()
    {
        $locale = app()->getLocale();
        $settings = $this->getSettings(1);
        
        if (!$settings) {
            return $this->getDefaultFrontendSettings();
        }
        
        return [
            'title' => $settings['title'][$locale] ?? $settings['title']['zh_TW'] ?? '',
            'description' => $settings['description'][$locale] ?? $settings['description']['zh_TW'] ?? '',
            'keywords' => $settings['keyword'][$locale] ?? $settings['keyword']['zh_TW'] ?? '',
            'tel' => $settings['tel'] ?? '',
            'email' => $settings['email'] ?? '',
            'fb' => $settings['fb'] ?? '',
            'ig' => $settings['ig'] ?? '',
            'line' => $settings['line'] ?? '',
            'youtube' => $settings['youtube'] ?? '',
            'app_google_play' => $settings['app_google_play'] ?? '',
            'app_apple_store' => $settings['app_apple_store'] ?? '',
            'ga_code' => $settings['ga_code'] ?? '',
            // 圖片路徑處理
            'favicon' => ($settings['favicon'] && isset($settings['favicon'][0]['path'])) ? 'uploads/' . $settings['favicon'][0]['path'] : null,
            'website_icon' => $settings['website_icon'] ? $this->resolveImageUrl($settings['website_icon']) : null,
        ];
    }
    
    /**
     * 取得預設前台設定
     *
     * @return array
     */
    private function getDefaultFrontendSettings()
    {
        return [
            'title' => config('app.name', 'SJTV'),
            'description' => '',
            'keywords' => '',
            'tel' => '',
            'email' => '',
            'fb' => '',
            'ig' => '',
            'line' => '',
            'youtube' => '',
            'app_google_play' => '',
            'app_apple_store' => '',
            'ga_code' => '',
            'favicon' => '',
            'website_icon' => '',
        ];
    }

    /**
     * 清除網站設定快取
     *
     * @param int $id 網站設定 ID
     * @return void
     */
    private function clearSettingsCache($id)
    {
        Cache::forget("website_settings_{$id}");
    }

}
