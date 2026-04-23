<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\EmailVerificationService;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

class UserService extends BaseService
{
    use CommonTrait;

    public function __construct(
        private UserRepository $userRepository,
        private EmailVerificationService $emailVerificationService
    ) {
        parent::__construct($userRepository);
    }

    /**
     * 會員註冊
     */
    public function register(array $data)
    {
        try {
            // 檢查 Email 是否已存在
            if ($this->userRepository->emailExists($data['email'])) {
                return $this->ReturnHandle(false, __('validation.unique'));
            }

            // 檢查用戶名是否已存在
            if (isset($data['username']) && $this->userRepository->usernameExists($data['username'])) {
                return $this->ReturnHandle(false, __('messages.member.username_taken'));
            }

            // 建立用戶（Repository 處理資料格式化）
            $user = $this->userRepository->save($data);

            // 自動登入新註冊的用戶
            Auth::login($user);

            // 發送驗證信
            $verificationResult = $this->emailVerificationService->generateVerificationToken($user);
            
            if (!$verificationResult['status']) {
                // 如果發送失敗，但用戶已建立，仍然返回成功但提示需要重發
                Log::warning('註冊成功但驗證信發送失敗', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $verificationResult['msg']
                ]);
                return $this->ReturnHandle(true, __('messages.member.register_success_no_email'), '/member/email-verification', $user);
            }

            return $this->ReturnHandle(true, __('messages.member.register_success'), '/member/email-verification', $user);

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('會員註冊失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => collect($data)->except(['password'])->toArray(),
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.register_failed'));
        }
    }

    /**
     * 會員登入
     */
    public function login(array $credentials, bool $remember = false)
    {
        try {
            // 檢查用戶是否存在
            $user = $this->userRepository->findByEmail($credentials['email']);

            if (!$user) {
                return $this->ReturnHandle(false, __('messages.member.login_invalid_credentials'));
            }

            // 檢查用戶是否啟用
            if (!$user->is_active) {
                return $this->ReturnHandle(false, __('messages.member.account_disabled'));
            }

            // 嘗試登入
            if (Auth::attempt($credentials, $remember)) {
                // 更新登入記錄
                $this->userRepository->updateLoginRecord($user);

                return $this->ReturnHandle(true, __('messages.member.login_success'), route('member.account'));
            }

            return $this->ReturnHandle(false, __('messages.member.login_invalid_credentials'));

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('會員登入失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $credentials['email'] ?? 'unknown',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.login_failed'));
        }
    }

    /**
     * 會員登出
     */
    /**
     * 取得格式化的當前會員資料
     */
    public function getFormattedCurrentUserData(): array
    {
        return $this->userRepository->getFormattedCurrentUserData();
    }

    /**
     * 更新當前會員資料
     */
    public function updateCurrentUserProfile(array $data)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->ReturnHandle(false, __('messages.member.please_login'));
            }

            // 使用 Repository 的 save 方法更新資料（會處理 address JSON 格式）
            $this->userRepository->save($data, $user->id);

            return $this->ReturnHandle(true, __('messages.member.profile_updated'));

        } catch (\Exception $e) {
            \Log::error('會員資料更新失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'data' => collect($data)->except(['password', 'password_confirmation'])->toArray()
            ]);

            return $this->ReturnHandle(false, __('messages.member.profile_update_failed'));
        }
    }

    public function logout()
    {
        try {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return $this->ReturnHandle(true, __('messages.member.logout_success'), 'home');

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('會員登出失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.logout_failed'));
        }
    }

    /**
     * 更新會員資料
     */
    public function updateProfile(User $user, array $data)
    {
        try {
            // 檢查用戶名是否已被其他用戶使用
            if (isset($data['username']) && $data['username'] !== $user->username) {
                $existingUser = $this->userRepository->findByUsername($data['username']);
                if ($existingUser && $existingUser->id !== $user->id) {
                    return $this->ReturnHandle(false, __('messages.member.username_taken'));
                }
            }

            // 過濾可更新的欄位
            $allowedFields = [
                'username', 'real_name', 'phone', 'birthdate', 
                'gender', 'address', 'avatar'
            ];

            $updateData = array_intersect_key($data, array_flip($allowedFields));

            $this->userRepository->update($user, $updateData);

            return $this->ReturnHandle(true, __('messages.member.profile_updated'));

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('會員資料更新失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $user->id,
                'data' => $updateData,
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.profile_update_failed'));
        }
    }

    /**
     * 發送密碼重設信
     */
    public function sendPasswordResetEmail(string $email)
    {
        try {
            $user = $this->userRepository->findByEmail($email);
            
            if (!$user) {
                return $this->ReturnHandle(false, __('messages.member.reset_not_found'));
            }

            if (!$user->is_active) {
                return $this->ReturnHandle(false, __('messages.member.account_disabled'));
            }

            // 生成重設 token
            $token = Str::random(60);
            
            // 儲存 token 到資料庫
            $this->userRepository->savePasswordResetToken($email, Hash::make($token));

            // 發送郵件
            // TODO: 建立 PasswordResetMail
            // Mail::to($user)->send(new PasswordResetMail($token));

            return $this->ReturnHandle(true, __('messages.member.reset_email_sent'));

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('密碼重設信發送失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.reset_email_failed'));
        }
    }

    /**
     * 重設密碼
     */
    public function resetPassword(string $token, string $email, string $password)
    {
        try {
            // 驗證 token
            $passwordReset = $this->userRepository->getPasswordResetToken($email);

            if (!$passwordReset || !Hash::check($token, $passwordReset->token)) {
                return $this->ReturnHandle(false, __('messages.member.reset_link_invalid'));
            }

            // 檢查 token 是否過期（1小時）
            if (now()->diffInMinutes($passwordReset->created_at) > 60) {
                return $this->ReturnHandle(false, __('messages.member.reset_link_expired'));
            }

            // 更新密碼
            $user = $this->userRepository->findByEmail($email);
            if (!$user) {
                return $this->ReturnHandle(false, __('messages.member.user_not_found'));
            }

            $this->userRepository->update($user, [
                'password' => Hash::make($password),
                'email_verified_at' => $user->email_verified_at ?: now(), // 重設密碼時順便驗證 email
            ]);

            // 刪除重設 token
            $this->userRepository->deletePasswordResetToken($email);

            return $this->ReturnHandle(true, __('messages.member.reset_success'), route('member.login'));

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('密碼重設失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.reset_failed'));
        }
    }

    /**
     * Email 驗證
     */
    public function verifyEmail(User $user)
    {
        try {
            if ($user->hasVerifiedEmail()) {
                return $this->ReturnHandle(false, 'Email 已經驗證過了');
            }

            $this->userRepository->update($user, [
                'email_verified_at' => now()
            ]);

            return $this->ReturnHandle(true, 'Email 驗證成功', 'member.account');

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('Email 驗證失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.email_verify_failed'));
        }
    }

    /**
     * 重發驗證信
     */
    public function resendVerificationEmail(User $user)
    {
        try {
            if ($user->hasVerifiedEmail()) {
                return $this->ReturnHandle(false, 'Email 已經驗證過了');
            }

            // 發送驗證信
            $verificationResult = $this->emailVerificationService->generateVerificationToken($user);
            
            if (!$verificationResult['status']) {
                return $this->ReturnHandle(false, $verificationResult['msg']);
            }

            return $this->ReturnHandle(true, __('messages.member.email_verification_sent'));

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('重發驗證信失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.reset_email_failed'));
        }
    }

    /**
     * 檢查用戶是否已驗證 Email
     */
    public function hasVerifiedEmail($userId)
    {
        $user = $this->userRepository->find($userId);
        return $user && !is_null($user->email_verified_at);
    }

    /**
     * 補完用戶資料（用於第三方登入後）
     */
    public function completeProfile($userId, array $data)
    {
        try {
            $user = $this->userRepository->find($userId);
            
            if (!$user) {
                return $this->ReturnHandle(false, __('messages.member.user_not_found'));
            }

            if ($user->profile_completed) {
                return $this->ReturnHandle(false, __('messages.member.profile_complete_required'));
            }

            // 更新用戶資料
            $updateData = [
                'name' => $data['name'],
                'birthdate' => $data['birthdate'],
                'gender' => $data['gender'],
                'city_id' => $data['address'], // 使用 city_id 讓 Repository 處理 JSON 格式
                'profile_completed' => 1  // 標記為已完成
            ];

            $this->userRepository->save($updateData, $user->id);

            return $this->ReturnHandle(true, __('messages.member.profile_completed'));

        } catch (\Exception $e) {
            // 記錄詳細錯誤到 log
            Log::error('補完資料失敗', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $userId,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            // 回傳通用錯誤訊息
            return $this->ReturnHandle(false, __('messages.member.profile_update_failed'));
        }
    }

    // ========== 後台管理相關方法 ==========

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active ? '啟用' : '停用';
        $model->event_status_title = "會員{$action}-{$model->name}";
    }

    /**
     * 取得分頁資料（後台用）
     */
    public function paginate($perPage, $sortColumn = 'created_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->userRepository->paginate($perPage, $sortColumn, $sortDirection, $filters);
    }

    /**
     * 取得單一會員詳細資料（後台用）
     */
    public function getFormData($id)
    {
        return $this->userRepository->findWithRelations($id);
    }

    /**
     * 覆寫 updateStatus 方法，處理會員狀態變更
     */
    public function updateStatus($id)
    {
        try {
            $member = $this->userRepository->find($id);
            if (!$member) {
                return $this->ReturnHandle(false, '會員不存在');
            }

            // 切換狀態
            $newStatus = !$member->is_active;
            $member->is_active = $newStatus;
            $member->save();

            // 設定事件標題
            $this->setStatusChangeEventType($member);

            // 記錄操作事件
            $this->eventService->fireDataChangeStatus($member);

            $statusText = $newStatus ? '啟用' : '停用';

            return $this->ReturnHandle(true, "會員狀態已{$statusText}");
        } catch (\Exception $e) {
            Log::error('切換會員狀態失敗', [
                'error' => $e->getMessage(),
                'member_id' => $id
            ]);

            return $this->ReturnHandle(false, '切換會員狀態失敗：' . $e->getMessage());
        }
    }
}