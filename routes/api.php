<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiNewsController;
use App\Http\Controllers\Api\ApiDramaController;
use App\Http\Controllers\Api\ApiProgramController;
use App\Http\Controllers\Api\ApiLiveController;
use App\Http\Controllers\Api\ApiRadioController;
use App\Http\Controllers\Api\ApiArticleController;
use App\Http\Controllers\Api\ViewController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ApiUserController;
use App\Http\Controllers\Api\ApiFactoryController;
use App\Http\Controllers\Api\ApiFrontendController;
use App\Http\Controllers\Api\AdminAuthController;
/*
|--------------------------------------------------------------------------
| 前台 API 路由（給 Vue 使用）
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->name('api.')->group(function () {
    
    // 首頁資料 - TODO: 需要建立 HomeController
    // Route::get('/home/hot-news', [HomeController::class, 'getHotNews'])->name('home.hot-news');
    // Route::get('/home/hot-dramas', [HomeController::class, 'getHotDramas'])->name('home.hot-dramas');
    // Route::get('/home/popular-programs', [HomeController::class, 'getPopularPrograms'])->name('home.popular-programs');
    // Route::get('/home/live-feeds', [HomeController::class, 'getLiveFeeds'])->name('home.live-feeds');
    // Route::get('/home/radios', [HomeController::class, 'getRadios'])->name('home.radios');
    // Route::get('/home/latest-news', [HomeController::class, 'getLatestNews'])->name('home.latest-news');


    // 最新消息相關
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [ApiNewsController::class, 'index'])->name('index');
    });
    
    // 影音相關
    Route::prefix('drama')->name('drama.')->group(function () {
        Route::get('/filter', [ApiDramaController::class, 'filter'])->name('filter');
        Route::get('/{id}', [ApiDramaController::class, 'show'])->name('show');
        Route::get('/{id}/episodes', [ApiDramaController::class, 'episodes'])->name('episodes');
    });
    
    // 節目相關
    Route::prefix('program')->name('program.')->group(function () {
        Route::get('/filter', [ApiProgramController::class, 'filter'])->name('filter');
        Route::get('/{id}', [ApiProgramController::class, 'show'])->name('show');
        Route::get('/{id}/episodes', [ApiProgramController::class, 'episodes'])->name('episodes');
    });
    
    // 直播相關
    Route::prefix('live')->name('live.')->group(function () {
        Route::get('/', [ApiLiveController::class, 'index'])->name('index');
        Route::get('/check-status', [ApiLiveController::class, 'checkStatus'])->name('check-status');
        Route::post('/batch-check-status', [ApiLiveController::class, 'batchCheckStatus'])->name('batch-check-status');
    });
    
    // 廣播相關
    Route::prefix('radio')->name('radio.')->group(function () {
        Route::get('/', [ApiRadioController::class, 'index'])->name('index');
        Route::get('/filter', [ApiRadioController::class, 'filter'])->name('filter');
        Route::get('/{id}', [ApiRadioController::class, 'show'])->name('show');
    });
    
    // 文章相關
    Route::prefix('articles')->name('articles.')->group(function () {
        Route::get('/', [ApiArticleController::class, 'index'])->name('index');
        Route::get('/category/{id}', [ApiArticleController::class, 'categoryArticles'])->name('category');
    });
    
    // 工廠相關
    Route::prefix('factories')->name('factories.')->group(function () {
        Route::get('/', [ApiFactoryController::class, 'index'])->name('index');
        Route::get('/regions', [ApiFactoryController::class, 'regions'])->name('regions');
        Route::get('/by-region', [ApiFactoryController::class, 'byRegion'])->name('by-region');
        Route::get('/{id}', [ApiFactoryController::class, 'show'])->name('show');
    });

    // ============================================
    // 前台整合 API（首頁系統 + 產品服務系統）
    // ============================================
    Route::prefix('frontend')->name('frontend.')->group(function () {
        // 🔥 整合 API（一次取得全部資料）
        Route::get('/all', [ApiFrontendController::class, 'all'])->name('all');
        
        // 首頁資料
        Route::get('/home', [ApiFrontendController::class, 'home'])->name('home');
        Route::get('/home/videos', [ApiFrontendController::class, 'homeVideos'])->name('home.videos');
        Route::get('/home/images', [ApiFrontendController::class, 'homeImages'])->name('home.images');
        
        // 產品服務
        Route::get('/product-services', [ApiFrontendController::class, 'productServices'])->name('product-services');
        Route::get('/product-services/matrix', [ApiFrontendController::class, 'productServicesMatrix'])->name('product-services.matrix');
        
        // 工廠
        Route::get('/factories', [ApiFrontendController::class, 'factories'])->name('factories');
        Route::get('/factories/{id}', [ApiFrontendController::class, 'factoryDetail'])->name('factories.detail');
        
        // 據點
        Route::get('/regions', [ApiFrontendController::class, 'regions'])->name('regions');
        
        // 片頭動畫
        Route::get('/intro-video', [ApiFrontendController::class, 'introVideo'])->name('intro-video');
        
        // 銷售據點圖片
        Route::get('/sales-locations', [ApiFrontendController::class, 'salesLocations'])->name('sales-locations');
        Route::get('/sales-locations/bilingual', [ApiFrontendController::class, 'salesLocationsBilingual'])->name('sales-locations.bilingual');
    });
    
    // 觀看數相關（公開功能）
    Route::prefix('views')->name('views.')->group(function () {
        // 訪客觀看記錄（不需登入）
        Route::post('/record-guest', [ViewController::class, 'recordGuest'])->name('record.guest');
        
        // 公開統計數據（不需登入）
        Route::get('/count/{contentType}/{contentId}', [ViewController::class, 'getCount'])->name('count');
        Route::post('/batch-counts', [ViewController::class, 'getBatchCounts'])->name('batch-counts');
        
        // 熱門內容與排行榜（公開）
        Route::get('/trending/{contentType}', [ViewController::class, 'getTrending'])->name('trending');
        Route::get('/rankings/{contentType}', [ViewController::class, 'getRankings'])->name('rankings');
        Route::get('/cross-rankings', [ViewController::class, 'getCrossRankings'])->name('cross-rankings');
        Route::get('/fastest-rising', [ViewController::class, 'getFastestRising'])->name('fastest-rising');
        
        // 分析數據（公開）
        Route::get('/analytics/{contentType}', [ViewController::class, 'getAnalytics'])->name('analytics');
        Route::get('/trend', [ViewController::class, 'getViewTrend'])->name('trend');
        Route::get('/time-slots', [ViewController::class, 'getPopularTimeSlots'])->name('time-slots');
    });
    
    
    // 影片相關 - TODO: 需要建立控制器
    // Route::get('/videos/{drama_id?}', [DramaController::class, 'getVideos'])->name('videos.list');
    
    // 搜尋相關
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/{type}', [SearchController::class, 'searchByType'])->name('type');
    });

    // 會員相關
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/search', [ApiUserController::class, 'search'])->name('search');
    });

    // 後台帳密檢查（無狀態，不建立登入）
    Route::prefix('admin-auth')->name('admin-auth.')->group(function () {
        Route::post('/check-credentials', [AdminAuthController::class, 'checkCredentials'])
            ->middleware('throttle:10,1')
            ->name('check-credentials');
    });
    
    // 需要登入的 API - TODO: 需要建立 MemberController
    // Route::middleware(['auth:sanctum'])->group(function () {
    //     // 收藏
    //     Route::post('/collection/add', [MemberController::class, 'addCollection'])->name('collection.add');
    //     Route::post('/collection/remove', [MemberController::class, 'removeCollection'])->name('collection.remove');
    //     
    //     // 觀看紀錄
    //     Route::post('/history/add', [MemberController::class, 'addHistory'])->name('history.add');
    // });
});

// 綠界金流 API (模組) - 在 v1 group 外面
require base_path('Modules/EcpayPayment/Routes/api.php');

// 訂單管理 API (模組)
require base_path('Modules/OrderManagement/Routes/api.php');

// 歷史訂單 API (模組)
require base_path('Modules/HistoryOrder/Routes/api.php');