<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 檢查用戶是否已登入
        if (!$user) {
            return redirect()->route('member.login');
        }

        // 檢查用戶是否已啟用
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('member.login')->with('error', '帳號已被停用，請聯繫客服');
        }

        // 檢查是否已驗證 Email
        if (!$user->hasVerifiedEmail()) {
            // AJAX 請求回傳 JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '請先驗證您的 Email 地址',
                    'redirect' => route('member.email-verification')
                ], 403);
            }

            // 一般請求導向驗證頁面
            return redirect()->route('member.email-verification');
        }

        return $next($request);
    }
}
