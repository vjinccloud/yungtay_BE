<?php
namespace App\Services;

use App\Repositories\BasicWebsiteSettingRepository;


class BasicWebsiteSettingService extends BaseService
{
    public function __construct(private BasicWebsiteSettingRepository $setting ) {
        parent::__construct($setting);
    }

    /**
     * 取得網站設定（格式化後的資料）
     */
    public function getSettings($id = 1, bool $failIfNotFound = false)
    {
        return $this->setting->getSettings($id, $failIfNotFound);
    }

    /**
     * 取得前台專用的網站設定
     * 
     * @return array
     */
    public function getFrontendSettings()
    {
        return $this->setting->getFrontendSettings();
    }

}
