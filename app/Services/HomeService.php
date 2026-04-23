<?php

namespace App\Services;

use App\Repositories\ArticleRepository;
use App\Repositories\DramaRepository;
use App\Repositories\ProgramRepository;
use App\Repositories\LiveRepository;
use App\Repositories\RadioRepository;
use App\Repositories\NewsRepository;
use App\Repositories\BannerRepository;
use Illuminate\Support\Facades\Cache;

class HomeService extends BaseService
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private DramaRepository $dramaRepository,
        private ProgramRepository $programRepository,
        private LiveRepository $liveRepository,
        private RadioRepository $radioRepository,
        private NewsRepository $newsRepository,
        private BannerRepository $bannerRepository
    ) {
        // parent::__construct() 不需要，因為 BaseService 沒有建構函式
    }

    /**
     * 取得首頁所有資料
     * 
     * @return array
     */
    public function getHomePageData(): array
    {
        $locale = app()->getLocale();
        $cacheKey = "homepage_data_{$locale}";

        // 快取 5 分鐘
        return Cache::remember($cacheKey, 300, function () {
            return [
                'banners' => $this->getBanners(),           // 輪播圖
                'latestFocus' => $this->getLatestFocus(),   // 最新焦點（最新消息）- 6則
                'articles' => $this->getArticles(),         // 新聞 - 9則（三天內觀看數優先，不足用發布時間補齊）
                'dramas' => $this->getDramas(),             // 影音 - 10則（觀看數優先）
                'programs' => $this->getPrograms(),         // 節目 - 10則（觀看數優先）
                'lives' => $this->getLives(),               // 直播 - 5則（修改時間）
                'radios' => $this->getRadios(),             // 廣播 - 8則（隨機）
            ];
        });
    }

    /**
     * 取得輪播圖
     */
    protected function getBanners()
    {
        return $this->bannerRepository->getActiveBanners();
    }

    /**
     * 取得最新焦點（最新消息）
     */
    protected function getLatestFocus()
    {
        return $this->newsRepository->getLatestFocus(6);
    }

    /**
     * 取得新聞（三天內觀看數優先，不足則用發布時間補齊）
     */
    protected function getArticles()
    {
        return $this->articleRepository->getHotNewsForHomePage(9, 3);
    }

    /**
     * 取得影音（觀看數優先）
     */
    protected function getDramas()
    {
        return $this->dramaRepository->getForHomePage(10);
    }

    /**
     * 取得節目（觀看數優先）
     */
    protected function getPrograms()
    {
        return $this->programRepository->getForHomePage(10);
    }

    /**
     * 取得直播（修改時間）
     */
    protected function getLives()
    {
        return $this->liveRepository->getForHomePage(5);
    }

    /**
     * 取得廣播（隨機）
     */
    protected function getRadios()
    {
        return $this->radioRepository->getForHomePage(8);
    }

    /**
     * 清除首頁快取
     */
    public function clearCache(): void
    {
        $locales = ['zh_TW', 'en'];
        foreach ($locales as $locale) {
            Cache::forget("homepage_data_{$locale}");
        }
    }
}