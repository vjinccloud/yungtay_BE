<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Frontend\HomeController;
// use App\Http\Controllers\Frontend\NewsController;
// use App\Http\Controllers\Frontend\DramaController;
// use App\Http\Controllers\Frontend\ProgramController;
// use App\Http\Controllers\Frontend\LiveController;
// use App\Http\Controllers\Frontend\RadioController;
// use App\Http\Controllers\Frontend\UserController;
// use App\Http\Controllers\Frontend\SocialAuthController;
// use App\Http\Controllers\Frontend\CollectionController;
// use App\Http\Controllers\Frontend\SearchController;
// use App\Http\Controllers\Frontend\VideoController;
// use App\Http\Controllers\Frontend\ArticleController;
// use App\Http\Controllers\Frontend\PasswordResetController;
// use App\Http\Controllers\SitemapController;

/*
|--------------------------------------------------------------------------
| 前台路由（已註解）
|--------------------------------------------------------------------------
*/

// 首頁路由（必須保留，給 route('home') 使用）
Route::get('/', function () {
    return redirect('/admin');  // 或改成你想要的首頁
})->name('home');

// // Sitemap 路由（SEO 優化）
// // 支援分語系、分檔的靜態 Sitemap 檔案
// // 檔案由 php artisan sitemap:generate 指令生成
// Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
// Route::get('/sitemaps/{filename}', [SitemapController::class, 'show'])
//     ->where('filename', '[a-z0-9\-]+\.xml')
//     ->name('sitemap.show');

// // 首頁
// Route::get('/', [HomeController::class, 'index'])->name('home');

// // 靜態頁面
// Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');

// // 客服中心
// Route::get('/customer-service', [HomeController::class, 'customerService'])->name('customer-service');
// Route::post('/customer-service/send', [HomeController::class, 'sendCustomerService'])->name('customer-service.send');

// // 新聞相關
// Route::prefix('news')->group(function () {
//     Route::get('/', [NewsController::class, 'index'])->name('news');
//     Route::get('/{id}', [NewsController::class, 'show'])->name('news.show');
// });

// // 新聞文章相關（hot news）
// Route::prefix('articles')->name('articles.')->group(function () {
//     Route::get('/', [ArticleController::class, 'index'])->name('index');
//     Route::get('/{id}', [ArticleController::class, 'show'])->name('show');
// });

// // 影音相關
// Route::prefix('drama')->name('drama.')->group(function () {
//     Route::get('/', [DramaController::class, 'index'])->name('index');
//     // 影片相關
//     Route::get('/{dramaId}/videos/', [VideoController::class, 'index'])->name('videos.index');
//     Route::get('/{dramaId}/video/{episodeId}', [VideoController::class, 'show'])->name('video.show');
// });

// Route::get('/episode/stream/{filePath}', [VideoController::class, 'streamEpisode'])
//     ->where('filePath', '.*')
//     ->name('episode.stream');

// // 節目相關
// Route::prefix('program')->name('program.')->group(function () {
//     Route::get('/', [ProgramController::class, 'index'])->name('index');
//     Route::get('/{programId}/videos', [VideoController::class, 'index'])->name('videos.index');
//     Route::get('/{programId}/video/{episodeId}', [VideoController::class, 'show'])->name('video.show');
// });

// // 直播相關
// Route::prefix('live')->name('live.')->group(function () {
//     Route::get('/{id?}', [LiveController::class, 'index'])->name('index');
// });

// // 廣播相關
// Route::prefix('radio')->name('radio.')->group(function () {
//     Route::get('/', [RadioController::class, 'index'])->name('index');
//     Route::get('/stream/{filePath}', [RadioController::class, 'streamAudio'])
//         ->where('filePath', '.*')
//         ->name('stream');
//     Route::get('/{id}', [RadioController::class, 'show'])->name('show');
// });

// // 搜尋功能
// Route::get('/search', [SearchController::class, 'index'])->name('search');

// // 會員相關路由
// Route::prefix('member')->name('member.')->group(function () {
//     // 僅限訪客（未登入用戶）
//     Route::middleware(['guest'])->group(function () {
//         Route::get('/login', [UserController::class, 'showLogin'])->name('login');
//         Route::post('/login', [UserController::class, 'login'])->name('login.post');
//         Route::get('/register', [UserController::class, 'showRegister'])->name('register');
//         Route::post('/register', [UserController::class, 'register'])->name('register.post');
        
//         // 密碼重設路由
//         Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.forgot');
//         Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
//         Route::get('/forgot-password-success', [PasswordResetController::class, 'showSuccessPage'])->name('password.success');
//         Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
//         Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
        
//         // 第三方登入重導向（僅限訪客）
//         Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
//     });
    
//     // 第三方登入回調（不限訪客，因為可能已自動登入）
//     Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

//     // Email 驗證相關（需要登入但不需要驗證）
//     Route::middleware(['auth'])->group(function () {
//         Route::get('/email-verification', [UserController::class, 'showEmailVerification'])->name('email-verification');
//         Route::post('/resend-verification', [UserController::class, 'resendVerification'])->name('resend-verification')->middleware('throttle:1,1');
//         Route::get('/email-verify', [UserController::class, 'verifyEmail'])->name('email-verify');
//         Route::get('/verification-complete', [UserController::class, 'verificationComplete'])->name('verification-complete');
        
//         // 補完資料頁面（只需登入，不需Email驗證）
//         Route::get('/complete-profile', [UserController::class, 'showCompleteProfile'])->name('complete-profile');
//         Route::post('/complete-profile', [UserController::class, 'completeProfile'])->name('complete-profile.post');
        
//         // 觀看記錄功能（只需登入，不需Email驗證）
//         Route::prefix('views')->name('views.')->group(function () {
//             Route::post('/record', [UserController::class, 'recordView'])->name('record');
//         });
//     });

//     // 需要登入且 Email 已驗證和資料完整
//     Route::middleware(['auth', App\Http\Middleware\EnsureEmailIsVerified::class, App\Http\Middleware\EnsureProfileCompleted::class])->group(function () {
//         Route::get('/account', [UserController::class, 'account'])->name('account');
//         Route::get('/profile', [UserController::class, 'profile'])->name('profile');
//         Route::post('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
//         Route::get('/collection', [UserController::class, 'collection'])->name('collection');
//         Route::get('/history', [UserController::class, 'history'])->name('history');
//         Route::get('/customer-service-records', [UserController::class, 'customerServiceRecords'])->name('customer-service-records');
//         Route::get('/notifications', [UserController::class, 'notifications'])->name('notifications');
//         Route::put('/notifications/{id}/read', [UserController::class, 'markNotificationAsRead'])->name('notifications.read');

//         // 會員收藏功能（需要Email驗證）
//         Route::prefix('collection')->name('collection.')->group(function () {
//             Route::post('/add', [CollectionController::class, 'add'])->name('add');
//             Route::post('/remove', [CollectionController::class, 'remove'])->name('remove');
//             Route::get('/getData', [CollectionController::class, 'getData'])->name('data');
//             Route::post('/check-status', [CollectionController::class, 'checkStatus'])->name('check-status');
//         });
        
//         // 會員觀看歷史和統計（需要Email驗證）
//         Route::prefix('views')->name('views.')->group(function () {
//             Route::get('/history', [UserController::class, 'getViewHistory'])->name('history');
//             Route::get('/stats', [UserController::class, 'getViewStats'])->name('stats');
//         });
//     });
    
//     // 登出不需要驗證
//     Route::middleware(['auth'])->group(function () {
//         Route::post('/logout', [UserController::class, 'logout'])->name('logout');
//     });
// });

// // 移除重複的客服中心路由（已在上方定義）



if (!function_exists('resourceWithPermissions')) {
    function resourceWithPermissions($prefix,$name, $controller, $permissions)
    {
        $baseName = $prefix.str_replace('.', '-', $name);

        if(isset($permissions['add'])){
            Route::get("$name/add", [$controller, 'create'])
            ->name("$baseName.add")
            ->middleware("permission:{$permissions['add']}");
        }

        if(isset($permissions['edit'])){
            Route::get("$name/edit/{id}", [$controller, 'edit'])
            ->name("$baseName.edit")
            ->middleware("permission:{$permissions['edit']}");
        }

        // 列表
        Route::get($name, [$controller, 'index'])
            ->name("$baseName")
            ->middleware("permission:{$permissions['index']}");

        // 创建
        Route::post($name, [$controller, 'store'])
            ->name("$baseName.store")
            ->middleware("permission:{$permissions['store']}");

        // 查看单个
        Route::get("$name/{id}", [$controller, 'show'])
            ->name("$baseName.show")
            ->middleware("permission:{$permissions['show']}");

        // 更新
        Route::put("$name/{id}", [$controller, 'update'])
            ->name("$baseName.update")
            ->middleware("permission:{$permissions['update']}");

        // 删除
        Route::delete("$name/{id}", [$controller, 'destroy'])
            ->name("$baseName.delete")
            ->middleware("permission:{$permissions['destroy']}");
    }
}

// 語系測試路由（開發用）
if (app()->environment('local')) {
    Route::get('/lang-test', function () {
        return response()->json([
            'current_locale' => app()->getLocale(),
            'session_locale' => session('locale'),
            'test_translation' => __('frontend.menu.home'),
            'supported_locales' => app(App\Services\LocalizationService::class)->getSupportedLocales(),
        ]);
    })->name('lang.test');
}


require 'admin.php';
