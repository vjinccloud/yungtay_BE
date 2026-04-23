<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialAuthController extends Controller
{
    public function __construct(
        private SocialAuthService $socialAuthService
    ) {}

    /**
     * 重導向至第三方登入頁面
     */
    public function redirect($provider)
    {
        if (!in_array($provider, ['google', 'line'])) {
            abort(404);
        }

        try {
            // 檢查設定是否完整
            $config = config("services.{$provider}");
            if (empty($config['client_id']) || empty($config['client_secret'])) {
                return redirect(route('member.login'))
                    ->with('error', "尚未設定 {$provider} 登入服務，請聯繫管理員");
            }
            
            // 本地開發環境跳過 SSL 驗證
            return Socialite::driver($provider)->setHttpClient(
                new \GuzzleHttp\Client(['verify' => false])
            )->redirect();
        } catch (Exception $e) {
            logger("Social login error for provider {$provider}: " . $e->getMessage());
            return redirect(route('member.login'))
                ->with('error', '第三方登入服務暫時無法使用，請稍後再試');
        }
    }

    /**
     * 處理第三方登入回調
     */
    public function callback(Request $request, $provider)
    {
        if (!in_array($provider, ['google', 'line'])) {
            abort(404);
        }

        try {
            // 檢查是否有錯誤
            if ($request->has('error')) {
                logger('Social login error from provider: ' . $request->get('error'));
                return redirect(route('member.login'))
                    ->with('error', __('messages.member.social_auth_cancelled'));
            }

            logger('Social login callback reached for provider: ' . $provider);
            
            // 本地開發環境跳過 SSL 驗證
            $socialUser = Socialite::driver($provider)->setHttpClient(
                new \GuzzleHttp\Client(['verify' => false])
            )->user();
            
            logger('Social user data: ', (array) $socialUser);
            
            $result = $this->socialAuthService->handleSocialLogin($provider, $socialUser);
            logger('Social login service result: ', $result);
            
            if ($result['status']) {
                // 檢查是否為新用戶需要補完資料
                if (isset($result['redirect']) && $result['redirect'] === 'member.complete-profile') {
                    return redirect(route('member.complete-profile'))
                        ->with('info', $result['msg']);
                }
                
                return redirect()->intended(route('member.account'))
                    ->with('success', $result['msg']);
            } else {
                return redirect(route('member.login'))
                    ->with('error', $result['msg']);
            }
            
        } catch (Exception $e) {
            logger('Social login error: ' . $e->getMessage());
            
            return redirect(route('member.login'))
                ->with('error', '登入過程中發生錯誤，請稍後再試');
        }
    }
}