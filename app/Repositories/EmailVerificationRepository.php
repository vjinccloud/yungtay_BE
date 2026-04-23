<?php

namespace App\Repositories;

use App\Models\EmailVerification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmailVerificationRepository extends BaseRepository
{
    public function __construct(EmailVerification $model)
    {
        parent::__construct($model);
    }

    /**
     * 建立驗證 token
     *
     * @param int $userId 用戶 ID
     * @param int $expirationHours 過期時間（小時），預設 24 小時
     * @return EmailVerification
     */
    public function createVerificationToken(int $userId, int $expirationHours = 24): EmailVerification
    {
        // 先刪除該用戶現有的驗證記錄
        $this->deleteByUserId($userId);

        // 生成隨機 token
        $rawToken = Str::random(60);
        
        // 使用 SHA256 加密儲存
        $hashedToken = hash('sha256', $rawToken);

        // 計算過期時間
        $expiresAt = Carbon::now()->addHours($expirationHours);

        // 建立新的驗證記錄
        $verification = $this->create([
            'user_id' => $userId,
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
        ]);

        // 將原始 token 附加到物件上，用於發送郵件
        $verification->raw_token = $rawToken;

        return $verification;
    }

    /**
     * 尋找有效的 token
     *
     * @param string $rawToken 原始 token
     * @return EmailVerification|null
     */
    public function findValidToken(string $rawToken): ?EmailVerification
    {
        $hashedToken = hash('sha256', $rawToken);
        
        return $this->queryFirst(function($query) use ($hashedToken) {
            return $query->byToken($hashedToken)
                        ->valid()
                        ->with('user');
        });
    }

    /**
     * 根據用戶 ID 刪除驗證記錄
     *
     * @param int $userId
     * @return int 刪除的記錄數
     */
    public function deleteByUserId(int $userId): int
    {
        return $this->model->where('user_id', $userId)->delete();
    }

    /**
     * 檢查 token 是否過期
     *
     * @param string $rawToken 原始 token
     * @return bool
     */
    public function isTokenExpired(string $rawToken): bool
    {
        $verification = $this->findValidToken($rawToken);
        
        if (!$verification) {
            return true; // 找不到記錄視為過期
        }

        return $verification->isExpired();
    }

    /**
     * 根據用戶 ID 查詢驗證記錄
     *
     * @param int $userId
     * @return EmailVerification|null
     */
    public function findByUserId(int $userId): ?EmailVerification
    {
        return $this->queryFirst(function($query) use ($userId) {
            return $query->byUserId($userId)->with('user');
        });
    }

    /**
     * 清理過期的 tokens
     *
     * @return int 清理的記錄數
     */
    public function cleanupExpiredTokens(): int
    {
        return $this->model->expired()->delete();
    }

    /**
     * 檢查用戶是否有有效的驗證 token
     *
     * @param int $userId
     * @return bool
     */
    public function hasValidToken(int $userId): bool
    {
        return $this->model->byUserId($userId)->valid()->exists();
    }

    /**
     * 取得用戶的有效驗證記錄
     *
     * @param int $userId
     * @return EmailVerification|null
     */
    public function getValidTokenByUserId(int $userId): ?EmailVerification
    {
        return $this->queryFirst(function($query) use ($userId) {
            return $query->byUserId($userId)
                        ->valid()
                        ->with('user');
        });
    }

    /**
     * 統計過期的 token 數量
     *
     * @return int
     */
    public function countExpiredTokens(): int
    {
        return $this->model->expired()->count();
    }

    /**
     * 統計有效的 token 數量
     *
     * @return int
     */
    public function countValidTokens(): int
    {
        return $this->model->valid()->count();
    }
}