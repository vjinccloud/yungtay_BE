<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmailVerification;
use App\Repositories\EmailVerificationRepository;
use App\Repositories\UserRepository;
use App\Mail\VerifyEmail;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailVerificationService
{
    use CommonTrait;

    protected EmailVerificationRepository $emailVerificationRepository;
    protected UserRepository $userRepository;

    public function __construct(
        EmailVerificationRepository $emailVerificationRepository,
        UserRepository $userRepository
    ) {
        $this->emailVerificationRepository = $emailVerificationRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * 產生驗證 token 並發送驗證信
     *
     * @param User $user 用戶物件
     * @param int $expirationHours 過期時間（小時），預設 24 小時
     * @return array ReturnHandle 格式的回應
     */
    public function generateVerificationToken(User $user, int $expirationHours = 24): array
    {
        try {
            // 檢查用戶是否已經驗證過
            if ($user->hasVerifiedEmail()) {
                return $this->ReturnHandle(false, 'Email 已經驗證過了');
            }

            DB::beginTransaction();

            // 建立新的驗證 token
            $verification = $this->emailVerificationRepository->createVerificationToken(
                $user->id, 
                $expirationHours
            );

            // 發送驗證信
            $mailResult = $this->sendVerificationEmail($user, $verification->raw_token);
            
            if (!$mailResult['status']) {
                DB::rollBack();
                return $mailResult;
            }

            DB::commit();

            // 記錄日誌
            Log::info('Email 驗證 token 已產生', [
                'user_id' => $user->id,
                'email' => $user->email,
                'expires_at' => $verification->expires_at->toDateTimeString(),
            ]);

            return $this->ReturnHandle(
                true, 
                '驗證信已發送到您的信箱，請檢查並點擊驗證連結',
                null,
                ['expires_at' => $verification->expires_at]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('產生 Email 驗證 token 失敗', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->ReturnHandle(false, '發送驗證信失敗：' . $e->getMessage());
        }
    }

    /**
     * 驗證 Email（檢查 token、標記已驗證、刪除 token）
     *
     * @param string $token 驗證 token
     * @return array ReturnHandle 格式的回應
     */
    public function verifyEmail(string $token): array
    {
        try {
            DB::beginTransaction();

            // 查詢有效的 token
            $verification = $this->emailVerificationRepository->findValidToken($token);

            if (!$verification) {
                DB::rollBack();
                return $this->ReturnHandle(false, '驗證連結無效或已過期');
            }

            // 檢查 token 是否過期
            if ($verification->isExpired()) {
                DB::rollBack();
                return $this->ReturnHandle(false, '驗證連結已過期，請重新申請驗證信');
            }

            $user = $verification->user;

            // 檢查用戶是否已經驗證過
            if ($user->hasVerifiedEmail()) {
                // 清理驗證記錄
                $this->emailVerificationRepository->deleteByUserId($user->id);
                DB::commit();
                
                return $this->ReturnHandle(false, 'Email 已經驗證過了');
            }

            // 標記 Email 為已驗證
            $user->markEmailAsVerified();

            // 刪除驗證記錄
            $this->emailVerificationRepository->deleteByUserId($user->id);

            DB::commit();

            // 記錄日誌
            Log::info('Email 驗證成功', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return $this->ReturnHandle(
                true, 
                'Email 驗證成功！歡迎加入我們',
                'member.account',
                ['user' => $user]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Email 驗證失敗', [
                'token' => substr($token, 0, 10) . '...',
                'error' => $e->getMessage(),
            ]);

            return $this->ReturnHandle(false, 'Email 驗證失敗：' . $e->getMessage());
        }
    }

    /**
     * 重新發送驗證信
     *
     * @param User $user 用戶物件
     * @return array ReturnHandle 格式的回應
     */
    public function resendVerificationEmail(User $user): array
    {
        try {
            // 檢查用戶是否已經驗證過
            if ($user->hasVerifiedEmail()) {
                return $this->ReturnHandle(false, 'Email 已經驗證過了');
            }

            // 檢查是否存在有效的 token（節流機制）
            $existingVerification = $this->emailVerificationRepository->getValidTokenByUserId($user->id);
            
            if ($existingVerification) {
                $createdMinutesAgo = $existingVerification->created_at->diffInMinutes(now());
                
                // 如果距離上次發送不到 1 分鐘，拒絕重新發送
                if ($createdMinutesAgo < 1) {
                    return $this->ReturnHandle(
                        false,
                        __('frontend.email_verify.resend_limit')
                    );
                }
            }

            // 重新產生驗證 token
            return $this->generateVerificationToken($user);

        } catch (\Exception $e) {
            Log::error('重新發送驗證信失敗', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->ReturnHandle(false, '重新發送驗證信失敗：' . $e->getMessage());
        }
    }

    /**
     * 清理過期的 tokens（可用於定時任務）
     *
     * @return array ReturnHandle 格式的回應
     */
    public function cleanupExpiredTokens(): array
    {
        try {
            $deletedCount = $this->emailVerificationRepository->cleanupExpiredTokens();

            Log::info('清理過期的 Email 驗證 tokens', [
                'deleted_count' => $deletedCount,
            ]);

            return $this->ReturnHandle(
                true, 
                "已清理 {$deletedCount} 個過期的驗證 token",
                null,
                ['deleted_count' => $deletedCount]
            );

        } catch (\Exception $e) {
            Log::error('清理過期 tokens 失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->ReturnHandle(false, '清理過期 tokens 失敗：' . $e->getMessage());
        }
    }

    /**
     * 發送驗證信（私有方法）
     *
     * @param User $user 用戶物件
     * @param string $rawToken 原始 token
     * @return array ReturnHandle 格式的回應
     */
    private function sendVerificationEmail(User $user, string $rawToken): array
    {
        try {
            // 發送郵件
            Mail::to($user->email)->send(new VerifyEmail($user, $rawToken));

            return $this->ReturnHandle(true, '驗證信發送成功');

        } catch (\Exception $e) {
            Log::error('發送 Email 驗證信失敗', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return $this->ReturnHandle(false, '發送驗證信失敗：' . $e->getMessage());
        }
    }

    /**
     * 取得驗證狀態統計
     *
     * @return array
     */
    public function getVerificationStats(): array
    {
        return [
            'pending_verifications' => $this->emailVerificationRepository->countValidTokens(),
            'expired_tokens' => $this->emailVerificationRepository->countExpiredTokens(),
            'verified_users' => $this->userRepository->getEmailVerifiedUsers()->count(),
        ];
    }

    /**
     * 檢查用戶驗證狀態
     *
     * @param User $user
     * @return array
     */
    public function getUserVerificationStatus(User $user): array
    {
        $status = [
            'is_verified' => $user->hasVerifiedEmail(),
            'has_pending_verification' => false,
            'verification_expires_at' => null,
        ];

        if (!$status['is_verified']) {
            $pendingVerification = $this->emailVerificationRepository->getValidTokenByUserId($user->id);
            
            if ($pendingVerification) {
                $status['has_pending_verification'] = true;
                $status['verification_expires_at'] = $pendingVerification->expires_at;
            }
        }

        return $status;
    }
}