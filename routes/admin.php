<?php

use Inertia\Inertia;
use App\Http\Middleware\LoadAdminUserRelations;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdministrationSettingController;
use App\Http\Controllers\Admin\BasicWebsiteSettingController;
use App\Http\Controllers\Admin\OperationLogController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\DramaCategoryController;
use App\Http\Controllers\Admin\ProgramCategoryController;
use App\Http\Controllers\Admin\DramaController;
use App\Http\Controllers\Admin\TempUploadController;
use App\Http\Controllers\Admin\DramaEpisodeController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\Admin\DramaThemeController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ProgramEpisodeController;
use App\Http\Controllers\Admin\ProgramThemeController;
use App\Http\Controllers\Admin\ModuleDescriptionController;
use App\Http\Controllers\Admin\LiveController;
use App\Http\Controllers\Admin\RadioController;
use App\Http\Controllers\Admin\RadioCategoryController;
use App\Http\Controllers\Admin\RadioEpisodeController;
use App\Http\Controllers\Admin\RadioThemeController;
use App\Http\Controllers\Admin\ArticleCategoryController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\MailRecipientController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\CustomerServiceController;
use App\Http\Controllers\Admin\MemberNotificationController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\NewsCategoryController;
use App\Http\Controllers\Admin\ExpertFieldController;
use App\Http\Controllers\Admin\ExpertCategoryController;
use App\Http\Controllers\Admin\ExpertController;
use App\Http\Controllers\Admin\ExpertArticleController;

Route::group(['prefix'  => 'admin'], function () {
    Route::match(['get', 'post'], '/', function () {
        // 如果已登入，重定向到第一個有權限的選單
        if (auth('admin')->check()) {
            $adminMenuService = app(\App\Services\AdminMenuService::class);
            $firstRoute = $adminMenuService->getFirstAvailableRoute();
            
            if ($firstRoute) {
                return redirect()->route($firstRoute);
            }
        }
        
        // 未登入或沒有權限，導向登入頁
        return redirect()->route('admin.login');
    });
    Route::group(['middleware' => ['guest:admin']], function () {
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('admin.login');
        Route::post('login', [AuthController::class, 'login'])->name('admin.login.post');
    });


    Route::group(['middleware' => ['auth:admin', LoadAdminUserRelations::class]], function () {
        Route::get('logout', [AuthController::class, 'logout'])->name('admin.logout');
        Route::put('edit-profile-form', [AuthController::class, 'update'])->name('admin.edit-profile-form');
        //管理員管理
        Route::group(['middleware' => ['permission:admin.admin-settings']], function () {

            Route::put('admin-settings/toggle-active', [AdminSettingController::class, 'toggleActive'])->name('admin.admin-settings.toggle-active')->middleware('permission:admin.admin-settings.edit');
            resourceWithPermissions(
                'admin.',
                'admin-settings',
                AdminSettingController::class,
                [
                    'index' => 'admin.admin-settings',
                    'add' => 'admin.admin-settings.add',      // ✅ 新增頁面權限
                    'store' => 'admin.admin-settings.add',
                    'edit' => 'admin.admin-settings.edit',    // ✅ 編輯頁面權限
                    'show' => 'admin.admin-settings.edit',
                    'update' => 'admin.admin-settings.edit',
                    'destroy' => 'admin.admin-settings.delete'
                ]
            );
        });

        //權限管理
        Route::group(['middleware' => ['permission:admin.administration-settings']], function () {
            resourceWithPermissions(
                'admin.',
                'administration-settings',
                AdministrationSettingController::class,
                [
                    'index' => 'admin.administration-settings',
                    'add' => 'admin.administration-settings.add',
                    'store' => 'admin.administration-settings.add',
                    'show' => 'admin.administration-settings.edit',
                    'edit' => 'admin.administration-settings.edit',
                    'update' => 'admin.administration-settings.edit',
                    'destroy' => 'admin.administration-settings.delete'
                ]
            );
        });


        //最新消息
        Route::group(['middleware' => ['permission:admin.news']], function () {
            Route::put('news/toggle-active', [NewsController::class, 'toggleActive'])->name('admin.news.toggle-active')->middleware('permission:admin.news.edit');
            Route::put('news/toggle-homepage-featured', [NewsController::class, 'toggleHomepageFeatured'])->name('admin.news.toggle-homepage-featured')->middleware('permission:admin.news.edit');
            Route::put('news/toggle-pinned', [NewsController::class, 'togglePinned'])->name('admin.news.toggle-pinned')->middleware('permission:admin.news.edit');
            resourceWithPermissions(
                'admin.',
                'news',
                NewsController::class,
                [
                    'index' => 'admin.news',
                    'add' => 'admin.news.add',
                    'store' => 'admin.news.add',
                    'show' => 'admin.news.edit',
                    'edit' => 'admin.news.edit',
                    'update' => 'admin.news.edit',
                    'destroy' => 'admin.news.delete'
                ]
            );
        });

        // 最新消息分類管理
        Route::group(['middleware' => ['permission:admin.news-categories']], function () {
            Route::put('news-categories/toggle-active', [NewsCategoryController::class, 'toggleActive'])
                ->name('admin.news-categories.toggle-active')
                ->middleware('permission:admin.news-categories.edit');

            Route::put('news-categories/sort', [NewsCategoryController::class, 'sort'])
                ->name('admin.news-categories.sort')
                ->middleware('permission:admin.news-categories.edit');

            resourceWithPermissions(
                'admin.',
                'news-categories',
                NewsCategoryController::class,
                [
                    'index' => 'admin.news-categories',
                    'add' => 'admin.news-categories.add',
                    'store' => 'admin.news-categories.add',
                    'show' => 'admin.news-categories.edit',
                    'edit' => 'admin.news-categories.edit',
                    'update' => 'admin.news-categories.edit',
                    'destroy' => 'admin.news-categories.delete'
                ]
            );
        });


        // ======== 影音系統 - 影音系統 ========
        //上傳檔案到暫存區
        Route::post('uploads/tmp/upload', [TempUploadController::class, 'upload'])->name('admin.uploads.tmp.upload')->withoutMiddleware([VerifyCsrfToken::class]);
        Route::post('uploads/tmp/remove', [TempUploadController::class, 'remove'])->name('admin.uploads.tmp.remove')->withoutMiddleware([VerifyCsrfToken::class]);
        Route::post('uploads/tmp/clear-all', [TempUploadController::class, 'clearAll'])->name('admin.uploads.tmp.clear-all')->withoutMiddleware([VerifyCsrfToken::class]);
        //影音分類管理
        Route::group(['middleware' => ['permission:admin.drama-categories']], function () {
            Route::delete('drama-categories/child/{id}', [DramaCategoryController::class, 'deleteChild'])->name('admin.drama-categories.delete-child');
            Route::put('drama-categories/toggle-active', [DramaCategoryController::class, 'toggleActive'])
                ->name('admin.drama-categories.toggle-active')
                ->middleware('permission:admin.drama-categories.edit');

            Route::put('drama-categories/sort', [DramaCategoryController::class, 'sort'])
                ->name('admin.drama-categories.sort')
                ->middleware('permission:admin.drama-categories.edit');

            resourceWithPermissions(
                'admin.',
                'drama-categories',
                DramaCategoryController::class,
                [
                    'index' => 'admin.drama-categories',
                    'add' => 'admin.drama-categories.add',
                    'store' => 'admin.drama-categories.add',
                    'show' => 'admin.drama-categories.edit',
                    'edit' => 'admin.drama-categories.edit',
                    'update' => 'admin.drama-categories.edit',
                    'destroy' => 'admin.drama-categories.delete'
                ]
            );
        });

        //影音管理
        Route::group(['middleware' => ['permission:admin.dramas']], function () {
            Route::put('dramas/toggle-active', [DramaController::class, 'toggleActive'])
                ->name('admin.dramas.toggle-active')
                ->middleware('permission:admin.dramas.edit');

            // 觀看紀錄統計頁面
            Route::get('dramas/{id}/view-logs', [DramaController::class, 'viewLogs'])
                ->name('admin.dramas.view-logs')
                ->middleware('permission:admin.dramas.view-logs');

            // 觀看紀錄資料 AJAX 端點
            Route::get('dramas/{id}/view-logs/data', [DramaController::class, 'viewLogsData'])
                ->name('admin.dramas.view-logs.data')
                ->middleware('permission:admin.dramas.view-logs');

            resourceWithPermissions(
                'admin.',
                'dramas',
                DramaController::class,
                [
                    'index' => 'admin.dramas',
                    'add' => 'admin.dramas.add',
                    'store' => 'admin.dramas.add',
                    'show' => 'admin.dramas.edit',
                    'edit' => 'admin.dramas.edit',
                    'update' => 'admin.dramas.edit',
                    'destroy' => 'admin.dramas.delete'
                ]
            );
        });


        //影音集數管理 (使用影音權限)
        Route::group(['middleware' => ['permission:admin.dramas']], function () {
            // 影片排序路由
            Route::put('dramas-episodes/sort', [DramaEpisodeController::class, 'sort'])
                ->name('admin.dramas-episodes.sort')
                ->middleware('permission:admin.dramas.edit');

            resourceWithPermissions(
                'admin.',
                'dramas-episodes',
                DramaEpisodeController::class,
                [
                    'index' => 'admin.dramas',
                    'add' => 'admin.dramas.add',
                    'store' => 'admin.dramas.add',
                    'show' => 'admin.dramas.edit',
                    'edit' => 'admin.dramas.edit',
                    'update' => 'admin.dramas.edit',
                    'destroy' => 'admin.dramas.delete'
                ]
            );
        });

        // 影音主題管理
        Route::group(['middleware' => ['permission:admin.drama-themes']], function () {
            //從主題中移除影音
            Route::delete('drama-themes/relations/{id}', [DramaThemeController::class, 'removeDrama'])
                ->name('admin.drama-themes.remove-drama')
                ->middleware('permission:admin.drama-themes.edit');
            //變更主題狀態
            Route::put('drama-themes/toggle-active', [DramaThemeController::class, 'toggleActive'])
                ->name('admin.drama-themes.toggle-active')
                ->middleware('permission:admin.drama-themes.edit');

            Route::put('drama-themes-relation/sort', [DramaThemeController::class, 'updateRelationSort'])
                ->name('admin.drama-themes-relation.sort')
                ->middleware('permission:admin.drama-themes.edit');

            // AJAX 資料列表路由
            Route::get('drama-themes/ajax-list', [DramaThemeController::class, 'ajaxList'])
                ->name('admin.drama-themes.ajax-list')
                ->middleware('permission:admin.drama-themes');

            // 更新主題下影音的排序
            Route::put('drama-themes/{dramaTheme}/dramas/sort', [DramaThemeController::class, 'updateDramaSort'])
                ->name('admin.drama-themes.dramas.sort')
                ->middleware('permission:admin.drama-themes.edit');

            // 查看主題下的影音列表
            Route::get('drama-themes/{dramaTheme}/dramas', [DramaThemeController::class, 'dramas'])
                ->name('admin.drama-themes.dramas')
                ->middleware('permission:admin.drama-themes');


            Route::put('drama-themes/sort', [DramaThemeController::class, 'sort'])
                ->name('admin.drama-themes.sort')
                ->middleware('permission:admin.drama-themes.edit');

            resourceWithPermissions(
                'admin.',
                'drama-themes',
                DramaThemeController::class,
                [
                    'index' => 'admin.drama-themes',
                    'add' => 'admin.drama-themes.add',
                    'store' => 'admin.drama-themes.add',  // 修正這行
                    'show' => 'admin.drama-themes.edit',
                    'edit' => 'admin.drama-themes.edit',
                    'update' => 'admin.drama-themes.edit',
                    'destroy' => 'admin.drama-themes.delete'
                ]
            );
        });

        //節目分類管理
        Route::group(['middleware' => ['permission:admin.program-categories']], function () {
            Route::delete('program-categories/child/{id}', [ProgramCategoryController::class, 'deleteChild'])->name('admin.program-categories.delete-child');
            Route::put('program-categories/toggle-active', [ProgramCategoryController::class, 'toggleActive'])
                ->name('admin.program-categories.toggle-active')
                ->middleware('permission:admin.program-categories.edit');
            Route::put('program-categories/sort', [ProgramCategoryController::class, 'sort'])
                ->name('admin.program-categories.sort')
                ->middleware('permission:admin.program-categories.edit');
            resourceWithPermissions(
                'admin.',
                'program-categories',
                ProgramCategoryController::class,
                [
                    'index' => 'admin.program-categories',
                    'add' => 'admin.program-categories.add',
                    'store' => 'admin.program-categories.add',
                    'show' => 'admin.program-categories.edit',
                    'edit' => 'admin.program-categories.edit',
                    'update' => 'admin.program-categories.edit',
                    'destroy' => 'admin.program-categories.delete'
                ]
            );
        });

        //節目管理
        Route::group(['middleware' => ['permission:admin.programs']], function () {
            Route::put('programs/toggle-active', [ProgramController::class, 'toggleActive'])
                ->name('admin.programs.toggle-active')
                ->middleware('permission:admin.programs.edit');

            // 觀看紀錄統計頁面
            Route::get('programs/{id}/view-logs', [ProgramController::class, 'viewLogs'])
                ->name('admin.programs.view-logs')
                ->middleware('permission:admin.programs.view-logs');

            // 觀看紀錄資料 AJAX 端點
            Route::get('programs/{id}/view-logs/data', [ProgramController::class, 'viewLogsData'])
                ->name('admin.programs.view-logs.data')
                ->middleware('permission:admin.programs.view-logs');

            resourceWithPermissions(
                'admin.',
                'programs',
                ProgramController::class,
                [
                    'index' => 'admin.programs',
                    'add' => 'admin.programs.add',
                    'store' => 'admin.programs.add',
                    'show' => 'admin.programs.edit',
                    'edit' => 'admin.programs.edit',
                    'update' => 'admin.programs.edit',
                    'destroy' => 'admin.programs.delete'
                ]
            );
        });

        //節目影音管理
        Route::group(['middleware' => ['permission:admin.programs']], function () {
            // 影片排序路由
            Route::put('programs-episodes/sort', [ProgramEpisodeController::class, 'sort'])
                ->name('admin.programs-episodes.sort')
                ->middleware('permission:admin.programs.edit');
            resourceWithPermissions(
                'admin.',
                'programs-episodes',
                ProgramEpisodeController::class,
                [
                    'index' => 'admin.programs',
                    'add' => 'admin.programs.add',
                    'store' => 'admin.programs.add',
                    'show' => 'admin.programs.edit',
                    'edit' => 'admin.programs.edit',
                    'update' => 'admin.programs.edit',
                    'destroy' => 'admin.programs.delete'
                ]
            );
        });

        //節目主題管理
        Route::group(['middleware' => ['permission:admin.program-themes']], function () {
            // AJAX 資料列表路由
            Route::get('program-themes/ajax-list', [ProgramThemeController::class, 'ajaxList'])
                ->name('admin.program-themes.ajax-list')
                ->middleware('permission:admin.program-themes');
            // 取得主題的節目列表
            Route::get('program-themes/{id}/programs', [ProgramThemeController::class, 'getPrograms'])
                ->name('admin.program-themes.programs')
                ->middleware('permission:admin.program-themes');

            // 新增節目到主題
            Route::post('program-themes/{id}/programs', [ProgramThemeController::class, 'addProgram'])
                ->name('admin.program-themes.add-program')
                ->middleware('permission:admin.program-themes.edit');

            // 從主題中移除節目
            Route::delete('program-themes/{id}/programs', [ProgramThemeController::class, 'removeProgram'])
                ->name('admin.program-themes.remove-program')
                ->middleware('permission:admin.program-themes.edit');

            // 更新主題中節目的排序
            Route::post('program-themes/{id}/sort-programs', [ProgramThemeController::class, 'sortPrograms'])
                ->name('admin.program-themes.sort-programs')
                ->middleware('permission:admin.program-themes.edit');

            //變更主題狀態
            Route::put('program-themes/toggle-active', [ProgramThemeController::class, 'toggleActive'])
                ->name('admin.program-themes.toggle-active')
                ->middleware('permission:admin.program-themes.edit');

            // 排序路由
            Route::post('program-themes/sort', [ProgramThemeController::class, 'sort'])
                ->name('admin.program-themes.sort')
                ->middleware('permission:admin.program-themes.edit');

            resourceWithPermissions(
                'admin.',
                'program-themes',
                ProgramThemeController::class,
                [
                    'index' => 'admin.program-themes',
                    'add' => 'admin.program-themes.add',
                    'store' => 'admin.program-themes.add',
                    'show' => 'admin.program-themes.edit',
                    'edit' => 'admin.program-themes.edit',
                    'update' => 'admin.program-themes.edit',
                    'destroy' => 'admin.program-themes.delete'
                ]
            );
        });

        // 直播管理
        Route::group(['middleware' => ['permission:admin.lives']], function () {
            Route::put('lives/toggle-active', [LiveController::class, 'toggleActive'])
                ->name('admin.lives.toggle-active')
                ->middleware('permission:admin.lives.edit');

            Route::put('lives/sort', [LiveController::class, 'sort'])
                ->name('admin.lives.sort')
                ->middleware('permission:admin.lives.edit');

            resourceWithPermissions(
                'admin.',
                'lives',
                LiveController::class,
                [
                    'index' => 'admin.lives',
                    'add' => 'admin.lives.add',
                    'store' => 'admin.lives.add',
                    'show' => 'admin.lives.edit',
                    'edit' => 'admin.lives.edit',
                    'update' => 'admin.lives.edit',
                    'destroy' => 'admin.lives.delete'
                ]
            );
        });

        // 廣播分類管理
        Route::group(['middleware' => ['permission:admin.radio-categories']], function () {
            Route::put('radio-categories/toggle-active', [RadioCategoryController::class, 'toggleActive'])
                ->name('admin.radio-categories.toggle-active')
                ->middleware('permission:admin.radio-categories.edit');

            Route::put('radio-categories/sort', [RadioCategoryController::class, 'sort'])
                ->name('admin.radio-categories.sort')
                ->middleware('permission:admin.radio-categories.edit');

            Route::delete('radio-categories/child/{id}', [RadioCategoryController::class, 'deleteChild'])
                ->name('admin.radio-categories.delete-child')
                ->middleware('permission:admin.radio-categories.edit');

            resourceWithPermissions(
                'admin.',
                'radio-categories',
                RadioCategoryController::class,
                [
                    'index' => 'admin.radio-categories',
                    'add' => 'admin.radio-categories.add',
                    'store' => 'admin.radio-categories.add',
                    'show' => 'admin.radio-categories.edit',
                    'edit' => 'admin.radio-categories.edit',
                    'update' => 'admin.radio-categories.edit',
                    'destroy' => 'admin.radio-categories.delete'
                ]
            );
        });

        // 廣播管理
        Route::group(['middleware' => ['permission:admin.radios']], function () {
            Route::put('radios/toggle-active', [RadioController::class, 'toggleActive'])
                ->name('admin.radios.toggle-active')
                ->middleware('permission:admin.radios.edit');

            Route::put('radios/sort', [RadioController::class, 'sort'])
                ->name('admin.radios.sort')
                ->middleware('permission:admin.radios.edit');

            resourceWithPermissions(
                'admin.',
                'radios',
                RadioController::class,
                [
                    'index' => 'admin.radios',
                    'add' => 'admin.radios.add',
                    'store' => 'admin.radios.add',
                    'show' => 'admin.radios.edit',
                    'edit' => 'admin.radios.edit',
                    'update' => 'admin.radios.edit',
                    'destroy' => 'admin.radios.delete'
                ]
            );

            // 觀看統計頁面
            Route::get('radios/{id}/view-stats', [RadioController::class, 'viewStats'])
                ->name('admin.radios.view-stats')
                ->middleware('permission:admin.radios.view-stats');

            // 廣播集數管理（AJAX）
            Route::prefix('radios/{radio}/episodes')->name('admin.radios.episodes.')->group(function () {
                Route::get('/', [RadioEpisodeController::class, 'index'])
                    ->name('index');
                Route::get('/next-episode-number', [RadioEpisodeController::class, 'nextEpisodeNumber'])
                    ->name('next-episode-number');
                Route::post('/', [RadioEpisodeController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:admin.radios.edit');
            });

            // 集數操作路由（不需要 radio 參數）
            Route::prefix('episodes')->name('admin.radios.episodes.')->group(function () {
                // 排序路由（從 request body 取得 radio_id 和 season）
                Route::post('/sort', [RadioEpisodeController::class, 'sort'])
                    ->name('sort')
                    ->middleware('permission:admin.radios.edit');
                // 讀取單筆集數（直接用 episode ID）
                Route::get('/{episode}', [RadioEpisodeController::class, 'show'])
                    ->name('show')
                    ->middleware('permission:admin.radios');
                // 更新集數（直接用 episode ID）
                Route::put('/{episode}', [RadioEpisodeController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:admin.radios.edit');
                // 刪除路由（直接用 episode ID）
                Route::delete('/{episode}', [RadioEpisodeController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:admin.radios.edit');
            });
        });

        // 廣播主題管理
        Route::group(['middleware' => ['permission:admin.radio-themes']], function () {
            // 從主題中移除廣播
            Route::delete('radio-themes/relations/{id}', [RadioThemeController::class, 'removeRadio'])
                ->name('admin.radio-themes.remove-radio')
                ->middleware('permission:admin.radio-themes.edit');

            // 變更主題狀態
            Route::put('radio-themes/toggle-active', [RadioThemeController::class, 'toggleActive'])
                ->name('admin.radio-themes.toggle-active')
                ->middleware('permission:admin.radio-themes.edit');

            Route::put('radio-themes-relation/sort', [RadioThemeController::class, 'updateRelationSort'])
                ->name('admin.radio-themes-relation.sort')
                ->middleware('permission:admin.radio-themes.edit');

            // AJAX 資料列表路由
            Route::get('radio-themes/ajax-list', [RadioThemeController::class, 'ajaxList'])
                ->name('admin.radio-themes.ajax-list')
                ->middleware('permission:admin.radio-themes');

            // 更新主題下廣播的排序
            Route::put('radio-themes/{radioTheme}/radios/sort', [RadioThemeController::class, 'updateRadioSort'])
                ->name('admin.radio-themes.radios.sort')
                ->middleware('permission:admin.radio-themes.edit');

            // 查看主題下的廣播列表
            Route::get('radio-themes/{radioTheme}/radios', [RadioThemeController::class, 'radios'])
                ->name('admin.radio-themes.radios')
                ->middleware('permission:admin.radio-themes');

            Route::put('radio-themes/sort', [RadioThemeController::class, 'sort'])
                ->name('admin.radio-themes.sort')
                ->middleware('permission:admin.radio-themes.edit');

            resourceWithPermissions(
                'admin.',
                'radio-themes',
                RadioThemeController::class,
                [
                    'index' => 'admin.radio-themes',
                    'add' => 'admin.radio-themes.add',
                    'store' => 'admin.radio-themes.add',
                    'show' => 'admin.radio-themes.edit',
                    'edit' => 'admin.radio-themes.edit',
                    'update' => 'admin.radio-themes.edit',
                    'destroy' => 'admin.radio-themes.delete'
                ]
            );
        });

        // 新聞分類管理
        Route::group(['middleware' => ['permission:admin.article-categories']], function () {
            Route::put('article-categories/toggle-active', [ArticleCategoryController::class, 'toggleActive'])
                ->name('admin.article-categories.toggle-active')
                ->middleware('permission:admin.article-categories.edit');

            Route::put('article-categories/sort', [ArticleCategoryController::class, 'sort'])
                ->name('admin.article-categories.sort')
                ->middleware('permission:admin.article-categories.edit');

            resourceWithPermissions(
                'admin.',
                'article-categories',
                ArticleCategoryController::class,
                [
                    'index' => 'admin.article-categories',
                    'add' => 'admin.article-categories.add',
                    'store' => 'admin.article-categories.add',
                    'show' => 'admin.article-categories.edit',
                    'edit' => 'admin.article-categories.edit',
                    'update' => 'admin.article-categories.edit',
                    'destroy' => 'admin.article-categories.delete'
                ]
            );
        });

        // 新聞管理
        Route::group(['middleware' => ['permission:admin.articles']], function () {
            Route::put('articles/toggle-active', [ArticleController::class, 'toggleActive'])
                ->name('admin.articles.toggle-active')
                ->middleware('permission:admin.articles.edit');

            resourceWithPermissions(
                'admin.',
                'articles',
                ArticleController::class,
                [
                    'index' => 'admin.articles',
                    'add' => 'admin.articles.add',
                    'store' => 'admin.articles.add',
                    'show' => 'admin.articles.edit',
                    'edit' => 'admin.articles.edit',
                    'update' => 'admin.articles.edit',
                    'destroy' => 'admin.articles.delete'
                ]
            );
        });

        // 輪播圖管理
        Route::group(['middleware' => ['permission:admin.banners']], function () {
            Route::put('banners/toggle-active', [BannerController::class, 'toggleActive'])
                ->name('admin.banners.toggle-active')
                ->middleware('permission:admin.banners.edit');

            Route::post('banners/sort', [BannerController::class, 'sort'])
                ->name('admin.banners.sort')
                ->middleware('permission:admin.banners.edit');

            resourceWithPermissions(
                'admin.',
                'banners',
                BannerController::class,
                [
                    'index' => 'admin.banners',
                    'add' => 'admin.banners.add',
                    'store' => 'admin.banners.add',
                    'show' => 'admin.banners.edit',
                    'edit' => 'admin.banners.edit',
                    'update' => 'admin.banners.edit',
                    'destroy' => 'admin.banners.delete',
                ]
            );
        });

        // 首頁圖片設定（模組）
        require base_path('Modules/HomeImageSetting/Routes/admin.php');

        // 首頁影片管理（模組）
        require base_path('Modules/HomeVideoSetting/Routes/admin.php');

        // 片頭動畫（模組）
        require base_path('Modules/IntroVideo/Routes/admin.php');

        // 銷售據點圖片管理（模組）
        require base_path('Modules/SalesLocationImage/Routes/admin.php');

        // 產品及服務（模組）
        require base_path('Modules/ProductService/Routes/admin.php');

        // 工廠服務設定（模組）
        require base_path('Modules/FactoryServiceSetting/Routes/admin.php');

        // 工廠設定（模組）
        require base_path('Modules/FactorySetting/Routes/admin.php');

        // 選單管理（模組）
        require base_path('Modules/MenuSetting/Routes/admin.php');

        // 前台選單管理（模組）
        require base_path('Modules/FrontMenuSetting/Routes/admin.php');

        // 商品規格設定（模組）
        require base_path('Modules/ProductSpecSetting/Routes/admin.php');

        // 商品上架管理（模組）
        require base_path('Modules/ProductListing/Routes/admin.php');

        // 訂單管理（模組）
        require base_path('Modules/OrderManagement/Routes/admin.php');

        // 歷史訂單（模組）
        require base_path('Modules/HistoryOrder/Routes/admin.php');

        // 滿額免運設定（模組）
        require base_path('Modules/PromotionActivity/Routes/admin.php');

        // 註冊購物金設定（模組）
        require base_path('Modules/RegisterBonus/Routes/admin.php');

        // 回饋活動管理（模組）
        require base_path('Modules/RewardActivity/Routes/admin.php');

        // Banner管理（模組）
        require base_path('Modules/BannerManagement/Routes/admin.php');

        // 最新消息分類管理（模組）
        require base_path('Modules/NewsCategoryManagement/Routes/admin.php');

        // 最新消息管理（模組）
        require base_path('Modules/NewsManagement/Routes/admin.php');

        // 贈品活動設定（模組）
        require base_path('Modules/GiftActivitySetting/Routes/admin.php');

        //網站基本資訊
        Route::group(['middleware' => ['permission:admin.basic-website-settings']], function () {
            Route::get('basic-website-settings', [BasicWebsiteSettingController::class, 'index'])->name('admin.basic-website-settings');
            Route::post('basic-website-settings', [BasicWebsiteSettingController::class, 'update'])->name('admin.basic-website-settings.update');
        });

        // 收件信箱管理
        Route::group(['middleware' => ['permission:admin.mail-recipients']], function () {
            Route::put('mail-recipients/toggle-active', [MailRecipientController::class, 'toggleActive'])
                ->name('admin.mail-recipients.toggle-active')
                ->middleware('permission:admin.mail-recipients.edit');

            resourceWithPermissions(
                'admin.',
                'mail-recipients',
                MailRecipientController::class,
                [
                    'index' => 'admin.mail-recipients',
                    'add' => 'admin.mail-recipients.add',
                    'store' => 'admin.mail-recipients.add',
                    'show' => 'admin.mail-recipients.edit',
                    'edit' => 'admin.mail-recipients.edit',
                    'update' => 'admin.mail-recipients.edit',
                    'destroy' => 'admin.mail-recipients.delete'
                ]
            );
        });

        // 模組描述管理
        Route::group(['middleware' => ['permission:admin.module-descriptions']], function () {
            resourceWithPermissions(
                'admin.',
                'module-descriptions',
                ModuleDescriptionController::class,
                [
                    'index' => 'admin.module-descriptions',
                    'add' => 'admin.module-descriptions.add',
                    'store' => 'admin.module-descriptions.add',
                    'show' => 'admin.module-descriptions.edit',
                    'edit' => 'admin.module-descriptions.edit',
                    'update' => 'admin.module-descriptions.edit',
                    'destroy' => 'admin.module-descriptions.delete'
                ]
            );
        });

        //操作紀錄
        Route::group(['middleware' => ['permission:admin.operation-logs']], function () {
            Route::get('operation-logs', [OperationLogController::class, 'index'])->name('admin.operation-logs');
        });

        // 通知系統
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
            Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
            Route::get('/manage', [NotificationController::class, 'manage'])->name('manage');
        });

        // 客服信件管理
        Route::group(['middleware' => ['permission:admin.customer-services']], function () {
            Route::get('customer-services', [CustomerServiceController::class, 'index'])
                ->name('admin.customer-services');
            Route::get('customer-services/{id}/show', [CustomerServiceController::class, 'show'])
                ->name('admin.customer-services.show')
                ->middleware('permission:admin.customer-services.show');
            Route::patch('customer-services/{id}/toggle-status', [CustomerServiceController::class, 'toggleStatus'])
                ->name('admin.customer-services.toggle-status')
                ->middleware('permission:admin.customer-services.update');
            Route::put('customer-services/{id}/update-note', [CustomerServiceController::class, 'updateNote'])
                ->name('admin.customer-services.update-note')
                ->middleware('permission:admin.customer-services.update');
            Route::put('customer-services/{id}/reply', [CustomerServiceController::class, 'reply'])
                ->name('admin.customer-services.reply')
                ->middleware('permission:admin.customer-services.reply');
            Route::delete('customer-services/{id}', [CustomerServiceController::class, 'destroy'])
                ->name('admin.customer-services.destroy')
                ->middleware('permission:admin.customer-services.delete');
        });

        // 會員通知管理
        Route::group(['middleware' => ['permission:admin.member-notifications']], function () {
            Route::get('member-notifications', [MemberNotificationController::class, 'index'])
                ->name('admin.member-notifications');
            Route::get('member-notifications/add', [MemberNotificationController::class, 'create'])
                ->name('admin.member-notifications.add')
                ->middleware('permission:admin.member-notifications.add');
            Route::post('member-notifications', [MemberNotificationController::class, 'store'])
                ->name('admin.member-notifications.store')
                ->middleware('permission:admin.member-notifications.add');
            Route::get('member-notifications/{id}', [MemberNotificationController::class, 'show'])
                ->name('admin.member-notifications.show')
                ->middleware('permission:admin.member-notifications.show');
            Route::get('member-notifications/{id}/recipients', [MemberNotificationController::class, 'recipients'])
                ->name('admin.member-notifications.recipients')
                ->middleware('permission:admin.member-notifications.show');
            Route::delete('member-notifications/{id}', [MemberNotificationController::class, 'destroy'])
                ->name('admin.member-notifications.delete')
                ->middleware('permission:admin.member-notifications.delete');
        });

        // 會員管理
        Route::group(['middleware' => ['permission:admin.members']], function () {
            Route::get('members', [MemberController::class, 'index'])
                ->name('admin.members');
            Route::get('members/{id}', [MemberController::class, 'show'])
                ->name('admin.members.show')
                ->middleware('permission:admin.members.show');
            Route::post('members/{id}/toggle-status', [MemberController::class, 'toggleStatus'])
                ->name('admin.members.toggle-status')
                ->middleware('permission:admin.members.toggle-status');
        });

        // ======== 專家管理系統 ========
        // 專家領域管理
        Route::group(['middleware' => ['permission:admin.expert-fields']], function () {
            Route::put('expert-fields/toggle-active', [ExpertFieldController::class, 'toggleActive'])
                ->name('admin.expert-fields.toggle-active')
                ->middleware('permission:admin.expert-fields.edit');
            Route::post('expert-fields/sort', [ExpertFieldController::class, 'sort'])
                ->name('admin.expert-fields.sort')
                ->middleware('permission:admin.expert-fields.edit');
            resourceWithPermissions(
                'admin.',
                'expert-fields',
                ExpertFieldController::class,
                [
                    'index' => 'admin.expert-fields',
                    'add' => 'admin.expert-fields.add',
                    'store' => 'admin.expert-fields.add',
                    'edit' => 'admin.expert-fields.edit',
                    'show' => 'admin.expert-fields.edit',
                    'update' => 'admin.expert-fields.edit',
                    'destroy' => 'admin.expert-fields.delete'
                ]
            );
        });

        // 專家分類管理
        Route::group(['middleware' => ['permission:admin.expert-categories']], function () {
            Route::put('expert-categories/toggle-active', [ExpertCategoryController::class, 'toggleActive'])
                ->name('admin.expert-categories.toggle-active')
                ->middleware('permission:admin.expert-categories.edit');
            Route::post('expert-categories/sort', [ExpertCategoryController::class, 'sort'])
                ->name('admin.expert-categories.sort')
                ->middleware('permission:admin.expert-categories.edit');
            resourceWithPermissions(
                'admin.',
                'expert-categories',
                ExpertCategoryController::class,
                [
                    'index' => 'admin.expert-categories',
                    'add' => 'admin.expert-categories.add',
                    'store' => 'admin.expert-categories.add',
                    'edit' => 'admin.expert-categories.edit',
                    'show' => 'admin.expert-categories.edit',
                    'update' => 'admin.expert-categories.edit',
                    'destroy' => 'admin.expert-categories.delete'
                ]
            );
        });

        // 專家管理
        Route::group(['middleware' => ['permission:admin.experts']], function () {
            Route::put('experts/toggle-active', [ExpertController::class, 'toggleActive'])
                ->name('admin.experts.toggle-active')
                ->middleware('permission:admin.experts.edit');
            Route::put('experts/toggle-featured', [ExpertController::class, 'toggleFeatured'])
                ->name('admin.experts.toggle-featured')
                ->middleware('permission:admin.experts.edit');
            Route::post('experts/sort', [ExpertController::class, 'sort'])
                ->name('admin.experts.sort')
                ->middleware('permission:admin.experts.edit');
            resourceWithPermissions(
                'admin.',
                'experts',
                ExpertController::class,
                [
                    'index' => 'admin.experts',
                    'add' => 'admin.experts.add',
                    'store' => 'admin.experts.add',
                    'edit' => 'admin.experts.edit',
                    'show' => 'admin.experts.edit',
                    'update' => 'admin.experts.edit',
                    'destroy' => 'admin.experts.delete'
                ]
            );
        });

        // 專家文章管理
        Route::group(['middleware' => ['permission:admin.expert-articles']], function () {
            Route::put('expert-articles/toggle-active', [ExpertArticleController::class, 'toggleActive'])
                ->name('admin.expert-articles.toggle-active')
                ->middleware('permission:admin.expert-articles.edit');
            Route::post('expert-articles/sort', [ExpertArticleController::class, 'sort'])
                ->name('admin.expert-articles.sort')
                ->middleware('permission:admin.expert-articles.edit');
            resourceWithPermissions(
                'admin.',
                'expert-articles',
                ExpertArticleController::class,
                [
                    'index' => 'admin.expert-articles',
                    'add' => 'admin.expert-articles.add',
                    'store' => 'admin.expert-articles.add',
                    'edit' => 'admin.expert-articles.edit',
                    'show' => 'admin.expert-articles.edit',
                    'update' => 'admin.expert-articles.edit',
                    'destroy' => 'admin.expert-articles.delete'
                ]
            );
        });

        // ======== 數據報表系統 ========
        // 新聞數據報表
        Route::get('analytics/articles', [AnalyticsController::class, 'articles'])
            ->name('admin.analytics.articles')
            ->middleware('permission:admin.analytics.articles');

        // 廣播數據報表
        Route::get('analytics/radios', [AnalyticsController::class, 'radios'])
            ->name('admin.analytics.radios')
            ->middleware('permission:admin.analytics.radios');

        // 影音主分類統計
        Route::get('analytics/dramas/main-categories', [AnalyticsController::class, 'dramaMainCategories'])
            ->name('admin.analytics.dramas.main-categories')
            ->middleware('permission:admin.analytics.dramas.main-categories');

        // 影音子分類統計
        Route::get('analytics/dramas/sub-categories', [AnalyticsController::class, 'dramaSubCategories'])
            ->name('admin.analytics.dramas.sub-categories')
            ->middleware('permission:admin.analytics.dramas.sub-categories');

        // 節目主分類統計
        Route::get('analytics/programs/main-categories', [AnalyticsController::class, 'programMainCategories'])
            ->name('admin.analytics.programs.main-categories')
            ->middleware('permission:admin.analytics.programs.main-categories');

        // 節目子分類統計
        Route::get('analytics/programs/sub-categories', [AnalyticsController::class, 'programSubCategories'])
            ->name('admin.analytics.programs.sub-categories')
            ->middleware('permission:admin.analytics.programs.sub-categories');

        // Dashboard 路由 - 重定向到第一個有權限的選單
        Route::get('dashboard', function () {
            $adminMenuService = app(\App\Services\AdminMenuService::class);
            $firstRoute = $adminMenuService->getFirstAvailableRoute();
            
            if ($firstRoute) {
                return redirect()->route($firstRoute);
            }
            
            // 如果沒有任何權限，登出並重定向到登入頁
            auth('admin')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            
            return redirect()->route('admin.login')->withErrors([
                'username' => '您沒有任何系統權限，請聯繫系統管理員'
            ]);
        })->name('admin.dashboard');
        
        Route::post('dashboard/clear-cache', [DashboardController::class, 'clearCache'])->name('admin.dashboard.clear-cache');
        Route::post('dashboard/recalculate-statistics', [DashboardController::class, 'recalculateViewStatistics'])->name('admin.dashboard.recalculate-statistics');
    });
});
