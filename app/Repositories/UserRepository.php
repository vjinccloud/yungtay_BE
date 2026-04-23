<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * 根據 Email 查詢用戶
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findBy('email', $email);
    }

    /**
     * 根據用戶名查詢用戶
     */
    public function findByUsername(string $username): ?User
    {
        return $this->findBy('username', $username);
    }

    /**
     * 取得總用戶數（已驗證）
     */
    public function getTotalVerifiedUsers(): int
    {
        try {
            return $this->model->whereNotNull('email_verified_at')->count();
        } catch (\Exception $e) {
            \Log::error('取得總用戶數失敗', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 取得今日註冊用戶數
     */
    public function getTodayRegisteredUsers(): int
    {
        try {
            return $this->model->whereDate('created_at', now()->format('Y-m-d'))->count();
        } catch (\Exception $e) {
            \Log::error('取得今日註冊用戶數失敗', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 取得最近註冊的用戶
     */
    public function getRecentUsers(int $limit = 5): array
    {
        try {
            return $this->model->whereNotNull('email_verified_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['id', 'name', 'email', 'created_at'])
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('取得最近用戶失敗', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 儲存會員資料（處理 address JSON 格式化）
     */
    public function save(array $attributes = [], $id = null)
    {
        // 處理密碼 hash（只有在填寫密碼時才更新）
        if (isset($attributes['password']) && !empty($attributes['password'])) {
            $attributes['password'] = \Hash::make($attributes['password']);
        } else {
            // 如果密碼為空，移除密碼欄位（不更新密碼）
            unset($attributes['password'], $attributes['password_confirmation']);
        }
        
        // 處理 address JSON 格式
        if (isset($attributes['city_id']) || isset($attributes['residence_city']) || isset($attributes['residence_area']) || isset($attributes['address'])) {
            $addressData = [
                'city_id' => $attributes['city_id'] ?? $attributes['residence_city'] ?? null,
                'area_id' => $attributes['area_id'] ?? $attributes['residence_area'] ?? null,
                'detail' => $attributes['address'] ?? null
            ];
            // 移除空值
            $addressData = array_filter($addressData, function($value) {
                return $value !== null && $value !== '';
            });
            $attributes['address'] = !empty($addressData) ? $addressData : null;
            
            // 清除原始欄位
            unset($attributes['city_id'], $attributes['residence_city'], $attributes['residence_area']);
        }
        
        // 設定預設值
        $attributes['is_active'] = $attributes['is_active'] ?? true;
        
        // 只有在建立新用戶時（沒有 id）才設定 email_verified_at 的預設值
        // 更新時不應該覆蓋已存在的 email_verified_at
        if (!$id && !array_key_exists('email_verified_at', $attributes)) {
            $attributes['email_verified_at'] = null;
        }
        // 處理姓名預設值
        if (!isset($attributes['name']) && isset($attributes['email'])) {
            $attributes['name'] = $attributes['email'];
        }
        
        return parent::save($attributes, $id);
    }

    /**
     * 取得格式化的當前會員資料（用於前端表單）
     */
    public function getFormattedCurrentUserData(): array
    {
        $user = auth()->user();
        
        // 處理 address JSON 欄位
        $addressData = $user->address && is_array($user->address) ? $user->address : [];
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'real_name' => $user->real_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'birthdate' => $user->birthdate?->format('Y-m-d'),
            'gender' => $user->gender,
            'id_number' => $user->id_number,
            'residence_city' => $addressData['city_id'] ?? '',
            'residence_area' => $addressData['area_id'] ?? '',
            'address' => $addressData['detail'] ?? '',
            'avatar' => $user->avatar,
            'is_active' => $user->is_active,
        ];
    }

    /**
     * 更新登入記錄
     */
    public function updateLoginRecord(User $user): bool
    {
        return $user->updateLoginRecord();
    }

    /**
     * 檢查 Email 是否已存在
     */
    public function emailExists(string $email): bool
    {
        return $this->model->where('email', $email)->exists();
    }

    /**
     * 檢查用戶名是否已存在
     */
    public function usernameExists(string $username): bool
    {
        return $this->model->where('username', $username)->exists();
    }

    /**
     * 取得啟用狀態的用戶
     */
    public function getActiveUsers()
    {
        return $this->model->active();
    }

    /**
     * 取得已驗證 Email 的用戶
     */
    public function getEmailVerifiedUsers()
    {
        return $this->model->emailVerified();
    }

    /**
     * 儲存或更新密碼重設 token
     */
    public function savePasswordResetToken(string $email, string $hashedToken)
    {
        return \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $hashedToken,
                'created_at' => now()
            ]
        );
    }

    /**
     * 取得密碼重設 token
     */
    public function getPasswordResetToken(string $email)
    {
        return \DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();
    }

    /**
     * 刪除密碼重設 token
     */
    public function deletePasswordResetToken(string $email)
    {
        return \DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();
    }

    /**
     * 取得今日密碼重設請求次數
     */
    public function getTodayResetRequestCount(string $email): int
    {
        return \DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * 搜尋會員（支援 Select2 AJAX）
     */
    public function searchMembers(string $keyword, int $perPage = 15)
    {
        $filters = ['search' => $keyword];

        return $this->model->active()           // 使用 BaseModelTrait 的 scope
            ->emailVerified()                   // 使用 User Model 的 scope
            ->filter($filters)                  // 使用 User Model 的 scopeFilter
            ->select(['id', 'name', 'email'])
            ->paginate($perPage);
    }

    /**
     * 取得會員分頁資料（後台管理用）
     *
     * @param int $perPage
     * @param string $sortColumn
     * @param string $sortDirection
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, $sortColumn = 'created_at', $sortDirection = 'desc', $filters = [])
    {
        $query = $this->model->newQuery()
            ->with(['city', 'area']);

        // 套用過濾條件
        if (!empty($filters)) {
            $query->filter($filters);
        }

        // 排序白名單
        $allowedSortColumns = ['id', 'name', 'email', 'phone', 'status', 'email_verified_at', 'created_at', 'updated_at', 'age'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        // 年齡排序轉換為生日反向排序
        if ($sortColumn === 'age') {
            $sortColumn = 'birthdate';
            // 年齡升序 = 生日降序，年齡降序 = 生日升序
            $sortDirection = $sortDirection === 'asc' ? 'desc' : 'asc';
        }

        return $query->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?: '-',
                'status' => $user->status,
                'is_active' => $user->is_active,  // 給前端 checkbox 用
                'registration_type' => $user->registration_type,
                'verification_status' => $user->verification_status,
                'full_address' => $user->full_address ?: '-',
                'gender' => $user->gender,
                'birthdate' => $user->birthdate?->format('Y-m-d'),
                'age' => $user->age,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * 取得單一會員詳細資料（包含關聯）
     *
     * @param int $id
     * @return array
     */
    public function findWithRelations($id)
    {
        $user = $this->model->newQuery()
            ->with(['city', 'area', 'socialAccounts'])
            ->findOrFail($id);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'birthdate' => $user->birthdate?->format('Y-m-d'),
            'age' => $user->age,
            'id_number' => $user->id_number,
            'address' => $user->address,
            'full_address' => $user->full_address,
            'avatar' => $user->avatar,
            'is_active' => $user->is_active,
            'profile_completed' => $user->profile_completed,
            'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
            'login_count' => $user->login_count,
            'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
            'registration_type' => $user->registration_type,
            'verification_status' => $user->verification_status,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            'socialAccounts' => $user->socialAccounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'provider' => $account->provider,
                    'provider_id' => $account->provider_id,
                    'created_at' => $account->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
        ];
    }

    /**
     * 更新會員狀態
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, $status)
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->update(['status' => $status]);
    }

    /**
     * 格式化居住地區
     *
     * @param User $user
     * @return string
     */
    private function formatLocation($user)
    {
        $location = [];

        if ($user->city) {
            $location[] = $user->city->name_zh_tw;
        }

        if ($user->area) {
            $location[] = $user->area->name_zh_tw;
        }

        return !empty($location) ? implode(' ', $location) : '-';
    }

}