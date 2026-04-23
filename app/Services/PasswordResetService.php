<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Services\BasicWebsiteSettingService;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetService
{
    use CommonTrait;

    protected $userRepository;
    protected $websiteSettingService;

    public function __construct(
        UserRepository $userRepository,
        BasicWebsiteSettingService $websiteSettingService
    ) {
        $this->userRepository = $userRepository;
        $this->websiteSettingService = $websiteSettingService;
    }

    /**
     * 發送密碼重設連結
     */
    public function sendResetLink($email)
    {
        // 檢查 Email 是否存在
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            return $this->ReturnHandle(false, __('messages.member.reset_not_found'));
        }
        
        // 檢查該 Email 今天是否已經請求超過 5 次
        $todayCount = $this->userRepository->getTodayResetRequestCount($email);
            
        if ($todayCount >= 5) {
            return $this->ReturnHandle(false, __('messages.member.reset_limit_exceeded'));
        }
        
        // 建立新的 token
        $token = Str::random(64);
        $hashedToken = Hash::make($token);
        
        // 儲存新的 token（會自動刪除舊的並建立新的）
        $this->userRepository->savePasswordResetToken($email, $hashedToken);
        
        // 發送郵件
        try {
            $this->sendResetEmail($user, $token);
            
            return $this->ReturnHandle(
                true, 
                __('messages.password_reset.link_sent_success'),
                route('member.password.success')
            );
        } catch (\Exception $e) {
            \Log::error('發送密碼重設郵件失敗: ' . $e->getMessage());
            return $this->ReturnHandle(false, __('messages.member.reset_email_failed'));
        }
    }

    /**
     * 重設密碼
     */
    public function resetPassword($email, $password, $token)
    {
        // 查找 token 記錄
        $resetRecord = $this->userRepository->getPasswordResetToken($email);
            
        if (!$resetRecord) {
            return $this->ReturnHandle(false, __('messages.member.reset_link_invalid'), route('member.login'));
        }
        
        // 驗證 token
        if (!Hash::check($token, $resetRecord->token)) {
            return $this->ReturnHandle(false, __('messages.member.reset_link_invalid'), route('member.login'));
        }
        
        // 檢查 token 是否過期（1小時）
        $tokenCreatedAt = Carbon::parse($resetRecord->created_at);
        if ($tokenCreatedAt->addHour()->isPast()) {
            // 刪除過期的 token
            $this->userRepository->deletePasswordResetToken($email);

            return $this->ReturnHandle(false, __('messages.member.reset_link_expired'), route('member.login'));
        }
        
        // 取得用戶並更新密碼
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            return $this->ReturnHandle(false, __('messages.member.user_not_found'));
        }
        
        // 使用 Repository 的 save 方法來更新密碼
        $this->userRepository->save(['password' => $password], $user->id);
        
        // 刪除使用過的 token
        $this->userRepository->deletePasswordResetToken($email);
        
        // 發送密碼已重設通知郵件（可選）
        // $this->sendPasswordChangedNotification($user);
        
        return $this->ReturnHandle(
            true, 
            __('messages.password_reset.reset_success'),
            route('member.login')
        );
    }

    /**
     * 發送重設密碼郵件
     */
    private function sendResetEmail($user, $token)
    {
        $resetUrl = route('member.password.reset', ['token' => $token]) . '?email=' . urlencode($user->email);
        
        // 取得當前語系和站點資訊
        $locale = app()->getLocale();
        $siteInfo = $this->websiteSettingService->getFrontendSettings();
        $siteName = $siteInfo['title'] ?? '信吉衛視';
        
        Mail::send("emails.password-reset-{$locale}", [
            'user' => $user,
            'resetUrl' => $resetUrl,
            'siteName' => $siteName
        ], function ($message) use ($user, $siteName) {
            $message->to($user->email, $user->name)
                    ->subject(__('emails.password_reset_subject', ['site' => $siteName]));
        });
    }

    /**
     * 發送密碼已變更通知
     */
    private function sendPasswordChangedNotification($user)
    {
        try {
            // 取得當前語系
            $locale = app()->getLocale();
            
            Mail::send("emails.password-changed-{$locale}", [
                'user' => $user,
                'changedAt' => Carbon::now()->format('Y-m-d H:i:s')
            ], function ($message) use ($user) {
                $siteInfo = $this->websiteSettingService->getFrontendSettings();
                $siteName = $siteInfo['title'] ?? '信吉衛視';
                
                $message->to($user->email, $user->name)
                        ->subject(__('emails.password_changed_subject', ['site' => $siteName]));
            });
        } catch (\Exception $e) {
            // 記錄錯誤但不影響主流程
            \Log::error(__('emails.password_changed_send_failed') . ': ' . $e->getMessage());
        }
    }
}