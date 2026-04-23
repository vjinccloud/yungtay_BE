<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    protected $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * 顯示忘記密碼頁面
     */
    public function showForgotForm()
    {
        return view('frontend.member.forgot-password');
    }

    /**
     * 處理忘記密碼請求
     */
    public function sendResetLink(Request $request)
    {
        // 頻率限制檢查
        $key = 'forgot-password:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'status' => false,
                'msg' => "請求次數過多，請在 {$seconds} 秒後再試"
            ], 429);
        }
        
        RateLimiter::hit($key, 60); // 60秒內最多5次
        
        // 驗證輸入
        $request->validate([
            'email' => 'required|email'
        ], [
            'email.required' => 'Email 為必填欄位',
            'email.email' => '請輸入有效的 Email 格式'
        ]);
        
        // 處理密碼重設
        $result = $this->passwordResetService->sendResetLink($request->email);
        
        // 根據結果回傳
        if (!$result['status']) {
            // 如果是 Email 不存在，回傳 404
            if (strpos($result['msg'], '尚未註冊') !== false) {
                return response()->json($result, 404);
            }
        }
        
        return response()->json($result);
    }

    /**
     * 顯示忘記密碼成功頁面
     */
    public function showSuccessPage()
    {
        return view('frontend.member.forgot-password-success');
    }

    /**
     * 顯示密碼重設頁面
     */
    public function showResetForm(Request $request, $token)
    {
        return view('frontend.member.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * 處理密碼重設
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();
        // 頻率限制
        $key = 'reset-password:' . $request->ip() . ':' . $data['email'];
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'status' => false,
                'msg' => "請求次數過多，請在 {$seconds} 秒後再試"
            ], 429);
        }
        
        RateLimiter::hit($key, 60);
        
        // 表單驗證已在 ResetPasswordRequest 中處理
        // 包含檢查新密碼是否與目前密碼相同
        
        // 重設密碼
        $result = $this->passwordResetService->resetPassword(
            $data['email'],
            $data['password'],
            $data['token']
        );
            
        return response()->json($result);
    }
}