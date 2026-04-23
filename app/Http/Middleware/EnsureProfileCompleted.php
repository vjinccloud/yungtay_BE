<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * 不需要檢查資料完整性的路由
     */
    protected $excludedRoutes = [
        'member.complete-profile',
        'member.complete-profile.post',
        'member.logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // 如果是排除的路由，直接通過
            $currentRouteName = $request->route()->getName();
            if (in_array($currentRouteName, $this->excludedRoutes)) {
                return $next($request);
            }
            
            // 檢查是否需要補完資料
            // profile_completed 被 cast 為 boolean：false 表示未完成，true 表示已完成
            if (!$user->profile_completed) {
                // 如果是 AJAX 請求
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => false,
                        'msg' => '請先完善個人資料以使用會員功能',
                        'redirect' => route('member.complete-profile')
                    ], 403);
                }
                
                // 一般請求導向資料補完頁面
                return redirect()->route('member.complete-profile')
                    ->with('warning', '請先完善個人資料才能使用會員功能');
            }
        }

        return $next($request);
    }
}