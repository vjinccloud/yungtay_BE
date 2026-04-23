<?php
// app/Repositories/WebsiteInfoRepository.php

namespace App\Repositories;

use App\Models\WebsiteInfo;
use App\Repositories\BaseRepository;

class WebsiteInfoRepository extends BaseRepository
{
    public function __construct(WebsiteInfo $websiteInfo)
    {
        parent::__construct($websiteInfo);
    }

    /**
     * 取得網站資訊（單一記錄）
     *
     * @return WebsiteInfo|null
     */
    public function getWebsiteInfo()
    {
        return $this->model->with(['favicon', 'updated_user'])->first();
    }

    /**
     * 更新網站資訊
     *
     * @param array $attributes
     * @return WebsiteInfo
     */
    public function updateWebsiteInfo(array $attributes)
    {
        $websiteInfo = $this->getWebsiteInfo();
        
        if (!$websiteInfo) {
            // 如果不存在則建立第一筆記錄
            $attributes['updated_by'] = auth('admin')->id();
            return $this->model->create($attributes);
        }

        $attributes['updated_by'] = auth('admin')->id();
        $websiteInfo->update($attributes);
        
        return $websiteInfo->fresh();
    }

    /**
     * 取得網站資訊給前台使用
     *
     * @return array
     */
    public function getWebsiteInfoForFrontend()
    {
        $websiteInfo = $this->getWebsiteInfo();
        
        if (!$websiteInfo) {
            return [];
        }

        $locale = app()->getLocale();
        
        return [
            'title' => $websiteInfo->getTranslation('title', $locale),
            'description' => $websiteInfo->getTranslation('description', $locale),
            'keyword' => $websiteInfo->getTranslation('keyword', $locale),
            'company_name' => $websiteInfo->getTranslation('company_name', $locale),
            'address' => $websiteInfo->getTranslation('address', $locale),
            'service_time' => $websiteInfo->service_time,
            'tax_id' => $websiteInfo->tax_id,
            'tel' => $websiteInfo->tel,
            'fax' => $websiteInfo->fax,
            'line' => $websiteInfo->line,
            'fb' => $websiteInfo->fb,
            'ig' => $websiteInfo->ig,
            'youtube' => $websiteInfo->youtube,
            'app_google_play' => $websiteInfo->app_google_play,
            'app_apple_store' => $websiteInfo->app_apple_store,
            'email' => $websiteInfo->email,
            'ga_code' => $websiteInfo->ga_code,
            'favicon' => $websiteInfo->favicon->first()?->path ?? null,
            'website_icon' => $websiteInfo->websiteIcon?->path ?? null,
        ];
    }

    /**
     * 取得網站資訊給後台編輯使用
     *
     * @return array
     */
    public function getWebsiteInfoForEdit()
    {
        $websiteInfo = $this->getWebsiteInfo();
        
        if (!$websiteInfo) {
            // 返回空的多語言結構
            return [
                'title' => ['zh_TW' => '', 'en' => ''],
                'description' => ['zh_TW' => '', 'en' => ''],
                'keyword' => ['zh_TW' => '', 'en' => ''],
                'company_name' => ['zh_TW' => '', 'en' => ''],
                'address' => ['zh_TW' => '', 'en' => ''],
                'service_time' => '',
                'tax_id' => '',
                'tel' => '',
                'fax' => '',
                'line' => '',
                'fb' => '',
                'ig' => '',
                'email' => '',
                'ga_code' => '',
                'favicon' => null,
            ];
        }

        return [
            'id' => $websiteInfo->id,
            'title' => [
                'zh_TW' => $websiteInfo->getTranslation('title', 'zh_TW'),
                'en' => $websiteInfo->getTranslation('title', 'en'),
            ],
            'description' => [
                'zh_TW' => $websiteInfo->getTranslation('description', 'zh_TW'),
                'en' => $websiteInfo->getTranslation('description', 'en'),
            ],
            'keyword' => [
                'zh_TW' => $websiteInfo->getTranslation('keyword', 'zh_TW'),
                'en' => $websiteInfo->getTranslation('keyword', 'en'),
            ],
            'company_name' => [
                'zh_TW' => $websiteInfo->getTranslation('company_name', 'zh_TW'),
                'en' => $websiteInfo->getTranslation('company_name', 'en'),
            ],
            'address' => [
                'zh_TW' => $websiteInfo->getTranslation('address', 'zh_TW'),
                'en' => $websiteInfo->getTranslation('address', 'en'),
            ],
            'service_time' => $websiteInfo->service_time ?? '',
            'tax_id' => $websiteInfo->tax_id ?? '',
            'tel' => $websiteInfo->tel ?? '',
            'fax' => $websiteInfo->fax ?? '',
            'line' => $websiteInfo->line ?? '',
            'fb' => $websiteInfo->fb ?? '',
            'ig' => $websiteInfo->ig ?? '',
            'email' => $websiteInfo->email ?? '',
            'ga_code' => $websiteInfo->ga_code ?? '',
            'favicon' => $websiteInfo->favicon->first()?->path ?? null,
            'updated_by' => $websiteInfo->updated_user?->name,
            'updated_at' => $websiteInfo->updated_at?->toDateTimeString(),
        ];
    }
}