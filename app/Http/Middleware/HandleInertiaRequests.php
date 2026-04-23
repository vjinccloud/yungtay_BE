<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\AdminMenuService;
use App\Services\OperationLogService;
use App\Services\BasicWebsiteSettingService;
use App\Services\NotificationService;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private AdminMenuService $adminMenu,
        private OperationLogService $operationLog,
        private BasicWebsiteSettingService $websiteSettingService,
        private NotificationService $notificationService
    ) {

    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     return $next($request);
    // }

    public function rootView(Request $request)
    {
        if ($request->is('admin/login')) {
            return 'admin.auth.login';
        }else if($request->is('admin/*')){
            return 'admin.app';
        }

        return 'app'; // 使用 resources/views/frontend.blade.php
    }


     /**
     * Define the props that are shared by default.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request)
    {
        $sharedData = [];
        if ($request->is('admin/*')) {
            $user = $request->user('admin');
            $user = $user ? $user->load('image') : null;
            $log =  $this->operationLog->record();
            
            // 載入網站基本設定（使用 getSettings 方法，享受快取優化）
            $websiteSettings = $this->websiteSettingService->getSettings(1);
            $faviconPath = $websiteSettings && !empty($websiteSettings['favicon'][0]['path']) ? asset('uploads/'.$websiteSettings['favicon'][0]['path']) : null;

            // 共享網站標題到 Blade 視圖，避免初始載入時顯示硬編碼標題
            $siteTitle = $websiteSettings && isset($websiteSettings['title']['zh_TW'])
                ? $websiteSettings['title']['zh_TW']
                : config('app.name', '後台管理系統');
            $websiteIconPath = $websiteSettings && isset($websiteSettings['website_icon']) ? asset($websiteSettings['website_icon']) : null;
            view()->share('adminSiteTitle', $siteTitle);
            view()->share('adminFavicon', $faviconPath);
            view()->share('adminOgImage', $websiteIconPath);

            $sharedData = [
                'auth' => [
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'img' => isset($user->image[0]) ? '/'.$user->image[0]->path : '/media/avatars/avatar15.jpg',

                    ] : null,
                ],
                'logs' => $log ? $log->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                        'message' => $item->message,
                        'user_name' => $item->created_user->name ?? null
                    ];
                })->toArray() : [],
                'menuItems' => $user ? $this->adminMenu->getMenu() : null,
                'thisMenu' => $user ? $this->adminMenu->getCurrentRouteMenu() : null,
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : null,
                'breadcrumbs' =>  $user ? $this->adminMenu->getLoadBreadcrumbs() : null,
                'flash' => [
                    'result' => fn() => $request->session()->get('result'),
                ],
                'websiteSettings' => [
                    'title' => $websiteSettings && isset($websiteSettings['title']['zh_TW']) ? $websiteSettings['title']['zh_TW'] : '信吉衛視後台管理系統',
                    'favicon' => $faviconPath,
                    'website_icon' => $websiteSettings && isset($websiteSettings['website_icon']) ? asset($websiteSettings['website_icon']) : null,
                    'copyright' => ($websiteSettings && isset($websiteSettings['title']['zh_TW']) ? $websiteSettings['title']['zh_TW'] : '信吉衛視後台管理系統') . '  版權所有 © ' . date('Y') .' All rights reserved.',
                ],
                'unreadNotificationCount' => $user ? $this->notificationService->getAdminUnreadCount($user->id) : 0,
                'translatableLocales' => config('translatable.locales', ['zh_TW' => ['label' => '中文', 'placeholder' => '請輸入中文']]),
                'translatablePrimary' => config('translatable.primary', 'zh_TW'),
            ];
        }

        return array_merge(parent::share($request), $sharedData);

    }
}
