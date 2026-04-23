<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\SocialAccountRepository;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthService extends BaseService
{
    use CommonTrait;

    public function __construct(
        private UserRepository $userRepository,
        private SocialAccountRepository $socialAccountRepository
    ) {}

    /**
     * 處理第三方登入
     */
    public function handleSocialLogin($provider, $socialUser)
    {
        try {
            // 1. 檢查是否已存在此第三方帳號
            $socialAccount = $this->socialAccountRepository->findByProviderAndId($provider, $socialUser->id);

            if ($socialAccount) {
                // 已存在：檢查帳號是否啟用
                $user = $socialAccount->user;

                // 檢查帳號是否被停用
                if (!$user->is_active) {
                    return $this->ReturnHandle(false, __('messages.member.account_disabled'));
                }

                $this->updateUserLoginInfo($user);
                Auth::guard('web')->login($user, true);

                return $this->ReturnHandle(true, '登入成功！');
            }

            // 2. 檢查email是否已存在（綁定既有帳號）
            if ($socialUser->email) {
                $existingUser = $this->userRepository->findByEmail($socialUser->email);
                
                if ($existingUser) {
                    // 檢查既有帳號是否被停用
                    if (!$existingUser->is_active) {
                        return $this->ReturnHandle(false, __('messages.member.account_disabled'));
                    }

                    // 綁定既有帳號
                    $socialAccountData = $this->prepareSocialAccountData($existingUser->id, $provider, $socialUser);
                    $this->socialAccountRepository->createWithTransaction($socialAccountData);

                    $this->updateUserLoginInfo($existingUser);
                    Auth::guard('web')->login($existingUser, true);

                    return $this->ReturnHandle(true, '成功綁定並登入！');
                }
            }

            // 3. 建立新用戶和社群帳號（資料未完整狀態）
            $newUser = $this->createNewSocialUser($provider, $socialUser);
            $socialAccountData = $this->prepareSocialAccountData($newUser->id, $provider, $socialUser);
            $this->socialAccountRepository->createWithTransaction($socialAccountData);

            $this->updateUserLoginInfo($newUser);
            Auth::guard('web')->login($newUser, true);
            
            // 新用戶需要強制補完資料
            return $this->ReturnHandle(true, '註冊成功！請完善個人資料以使用會員功能', 'member.complete-profile');

        } catch (\Exception $e) {
            logger('Social login service error: ' . $e->getMessage());
            return $this->ReturnHandle(false, '登入過程中發生錯誤，請稍後再試');
        }
    }

    /**
     * 建立新的社群用戶（基礎資料，需要後續補完）
     */
    private function createNewSocialUser($provider, $socialUser)
    {
        $username = $this->generateUniqueUsername($socialUser->name ?? $socialUser->nickname ?? $provider . '_user');
        
        $userData = [
            'name' => $socialUser->name ?? $socialUser->nickname ?? $username,
            'email' => $socialUser->email,
            'username' => $username,
            'password' => bcrypt(Str::random(32)), // 隨機密碼
            'avatar' => $socialUser->avatar,
            'is_active' => 1,
            'email_verified_at' => date('Y-m-d H:i:s'), // 第三方登入視為已驗證
            'profile_completed' => 0, // 標記為資料未完整
            // 移除第三方 ID 寫入，改用 social_accounts 表格管理
            // 必要欄位設為 null，強制用戶補完
            'phone' => null,
            'birthdate' => null,
            'gender' => null,
        ];

        return $this->userRepository->save($userData);
    }

    /**
     * 準備社群帳號資料
     */
    private function prepareSocialAccountData($userId, $provider, $socialUser)
    {
        return [
            'user_id' => $userId,
            'provider' => $provider,
            'provider_id' => $socialUser->id,
            'provider_email' => $socialUser->email,
            'provider_name' => $socialUser->name,
            'provider_avatar' => $socialUser->avatar,
            'provider_data' => $socialUser->user ?? []
        ];
    }

    /**
     * 產生唯一的用戶名
     */
    private function generateUniqueUsername($baseName)
    {
        $baseName = preg_replace('/[^a-zA-Z0-9_]/', '', $baseName);
        if (empty($baseName)) {
            $baseName = 'user';
        }

        $username = strtolower($baseName);
        $counter = 1;

        while ($this->userRepository->usernameExists($username)) {
            $username = strtolower($baseName) . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * 更新用戶登入資訊
     */
    private function updateUserLoginInfo(User $user)
    {
        $updateData = [
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
        ];
        
        $this->userRepository->save($updateData, $user->id);
    }

    /**
     * 解綁第三方帳號
     */
    public function unlinkSocialAccount($userId, $provider)
    {
        try {
            $socialAccount = $this->socialAccountRepository->findByUserAndProvider($userId, $provider);
            
            if (!$socialAccount) {
                return $this->ReturnHandle(false, '找不到要解綁的帳號');
            }

            $this->socialAccountRepository->deleteWithTransaction($socialAccount->id);
            
            return $this->ReturnHandle(true, '解綁成功');
            
        } catch (\Exception $e) {
            logger('Unlink social account error: ' . $e->getMessage());
            return $this->ReturnHandle(false, '解綁失敗，請稍後再試');
        }
    }

    /**
     * 取得用戶的社群帳號列表
     */
    public function getUserSocialAccounts($userId)
    {
        return $this->socialAccountRepository->getUserSocialAccounts($userId);
    }
}