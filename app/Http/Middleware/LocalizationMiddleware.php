<?php

namespace App\Http\Middleware;

use App\Services\LocalizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    public function __construct(
        private LocalizationService $localizationService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // 1. 優先檢查 URL 參數
        if ($request->has('lang')) {
            $locale = $request->get('lang');
        }
        // 2. 檢查 Session
        elseif (session()->has('locale')) {
            $locale = session('locale');
        }
        // 3. 預設值
        else {
            $locale = 'zh_TW';
        }

        // 設定語系（包含驗證）
        $this->localizationService->setLocale($locale);

        return $next($request);
    }
}