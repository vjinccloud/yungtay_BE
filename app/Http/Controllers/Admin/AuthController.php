<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Http\Requests\AdminUser\EditProfileFormRequest;
use App\Services\AdminUserService;
use App\Services\EventService;
use App\Services\AdminMenuService;

class AuthController extends Controller
{
    /**
     * Where to redirect admins after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        private AdminUserService $adminUser,
        private \App\Services\BasicWebsiteSettingService $websiteSettingService,
        private AdminMenuService $adminMenuService,
    )
    {
        $this->eventService = app(EventService::class);
    }
    public function showLoginForm()
    {
        $captchaUrl = captcha_src('math') . '?' . time();
        
        // 載入網站基本設定
        $websiteSettings = $this->websiteSettingService->find(1);
        $websiteName = $websiteSettings['title']['zh_TW'] ?? '信吉衛視';

        return Inertia::render('Admin/Auth/Login',[
            'captchaUrl' => $captchaUrl,
            'websiteName' => $websiteName,
            'pageTitle' => $websiteName . ' - 後台管理系統',
        ]);
    }

    public function login(Request $request)
    {
        // 驗證輸入資料
        $credentials = $request->validate([
            'username' => 'required|email',
            'password' => 'required|min:6',
            // 'captcha'  => 'required|captcha',
        ], [
            'username.required' => '請輸入帳號',
            'username.email' => '請輸入有效的電子郵件格式',
            'password.required' => '請輸入密碼',
            'password.min' => '密碼長度至少需要 6 個字元',
            'captcha.required' => '請輸入驗證碼',
            'captcha.captcha' => '驗證碼錯誤，請重新輸入',
        ]);

        // 使用 AdminUserService 搜尋用戶
        $adminUser = $this->adminUser->findBy('email', $credentials['username']);

        if (!$adminUser) {
            // 記錄登入失敗
            \Log::warning('登入失敗：帳號不存在', ['username' => $credentials['username'], 'ip' => $request->ip()]);
            
            throw ValidationException::withMessages([
                'username' => '帳號或密碼錯誤', // 為了安全性，不透露是帳號還是密碼錯誤
            ]);
        }

        // 檢查帳號狀態
        if ($adminUser->is_active != 1) {
            // 記錄停用帳號登入嘗試
            \Log::warning('登入失敗：帳號已停用', ['username' => $credentials['username'], 'ip' => $request->ip()]);
            
            throw ValidationException::withMessages([
                'username' => '此帳號已被停用，請聯繫系統管理員',
            ]);
        }

        // 嘗試登入
        $remember = $request->boolean('remember');
        
        if (Auth::guard('admin')->attempt([
            'email' => $credentials['username'],
            'password' => $credentials['password']
        ], $remember)) {
            
            // 重新生成 session ID 防止 session 固定攻擊
            $request->session()->regenerate();
            
            // 記錄登入事件
            $this->eventService->fireUserLoggedIn(Auth::guard('admin'));
            
            // 記錄成功登入
            \Log::info('管理員登入成功', [
                'user_id' => Auth::guard('admin')->id(),
                'username' => $credentials['username'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            // 取得使用者有權限的第一個選單路由
            $firstAvailableRoute = $this->adminMenuService->getFirstAvailableRoute();
            
            // 如果有 intended URL 且不是 dashboard，使用它；否則導向第一個有權限的選單
            $intendedUrl = session()->pull('url.intended');
            
            if ($intendedUrl && !str_contains($intendedUrl, '/admin/dashboard')) {
                $redirectUrl = $intendedUrl;
            } elseif ($firstAvailableRoute) {
                $redirectUrl = route($firstAvailableRoute);
            } else {
                // 如果沒有任何可用的選單，導向登入頁並顯示錯誤
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                throw ValidationException::withMessages([
                    'username' => '您沒有任何系統權限，請聯繫系統管理員',
                ]);
            }
            
            return Inertia::location($redirectUrl);
        }

        // 記錄密碼錯誤
        \Log::warning('登入失敗：密碼錯誤', ['username' => $credentials['username'], 'ip' => $request->ip()]);
        
        throw ValidationException::withMessages([
            'password' => '帳號或密碼錯誤', // 為了安全性，不透露是帳號還是密碼錯誤
        ]);
    }

    /**
     * 登出功能
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // 記錄登出事件
        if (Auth::guard('admin')->check()) {
            \Log::info('管理員登出', [
                'user_id' => Auth::guard('admin')->id(),
                'username' => Auth::guard('admin')->user()->email,
                'ip' => $request->ip()
            ]);
        }
        
        // 執行登出
        Auth::guard('admin')->logout();
        
        // 清除並重新生成 session
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // 重導向到登入頁面，並顯示成功訊息
        return redirect()->route('admin.login')->with('status', '您已成功登出系統');
    }

    public function update(EditProfileFormRequest $request){
        $validated = $request->validated();
        $id = Auth::guard('admin')->id();
        $data = collect($validated)->only(['email', 'name', 'password', 'slim'])->toArray();
        $data['event_type'] = '個人基本資料';
        $result =  $this->adminUser->save($data, $id);
        $routeUrl = $request->input('routeUrl');
        $component = $request->input('component');
        $result['redirect'] = $routeUrl;
        return redirect()
        ->back()
        ->with('result', $result);
    }

}
