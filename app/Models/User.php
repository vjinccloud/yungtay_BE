<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BaseModelTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone',
        'birthdate',
        'gender',
        'id_number',
        'address',
        'avatar',
        'is_active',
        'profile_completed',
        'last_login_at',
        'login_count',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
            'address' => 'array',
            'last_login_at' => 'datetime',
            'login_count' => 'integer',
            'is_active' => 'boolean',
            'profile_completed' => 'boolean',
        ];
    }

    /**
     * 關聯：用戶居住城市
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(ListCity::class, 'address->city_sn', 'sn');
    }

    /**
     * 關聯：用戶居住區域
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function area()
    {
        return $this->belongsTo(ListArea::class, 'address->area_sn', 'sn');
    }

    /**
     * 關聯：Email 驗證記錄
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function emailVerification()
    {
        return $this->hasOne(EmailVerification::class);
    }

    /**
     * 屬性存取器：取得完整地址
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        if (!$this->address) {
            return '';
        }

        $cityName = '';
        $areaName = '';
        $detail = $this->address['detail'] ?? '';

        // 如果有城市編號，取得城市名稱（支援兩種欄位名稱）
        $cityId = $this->address['city_id'] ?? $this->address['city_sn'] ?? null;
        if ($cityId) {
            $city = ListCity::find($cityId);
            $cityName = $city?->title ?? '';
        }

        // 如果有區域編號，取得區域名稱（支援兩種欄位名稱）
        $areaId = $this->address['area_id'] ?? $this->address['area_sn'] ?? null;
        if ($areaId) {
            $area = ListArea::find($areaId);
            $areaName = $area?->title ?? '';
        }

        return trim($cityName . $areaName . $detail);
    }

    /**
     * 屬性存取器：取得顯示名稱（使用姓名）
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * 屬性存取器：取得大頭貼 URL
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        // 如果是完整 URL（第三方頭像）
        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        // 如果是本地檔案
        return asset('storage/' . $this->avatar);
    }

    /**
     * 屬性存取器：取得年齡
     *
     * @return int|null
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birthdate) {
            return null;
        }

        return $this->birthdate->age;
    }

    /**
     * 屬性存取器：取得註冊方式
     *
     * @return string
     */
    public function getRegistrationTypeAttribute(): string
    {
        // 檢查是否有社群帳號關聯
        if ($this->socialAccounts()->exists()) {
            $provider = $this->socialAccounts()->first()->provider;
            return match($provider) {
                'google' => 'Google',
                'line' => 'LINE',
                default => ucfirst($provider)
            };
        }

        return '一般註冊';
    }

    /**
     * 屬性存取器：取得驗證狀態
     *
     * @return string
     */
    public function getVerificationStatusAttribute(): string
    {
        $hasSocialAccount = $this->socialAccounts()->exists();
        $emailVerified = !is_null($this->email_verified_at);
        $profileCompleted = $this->profile_completed;

        // 第三方登入且資料未完整
        if ($hasSocialAccount && !$profileCompleted) {
            return '待補充資料';
        }

        // Email 未驗證（包含一般註冊和第三方登入）
        if (!$emailVerified) {
            return '待驗證';
        }

        // 全部完成
        return '已完成';
    }


    /**
     * Scope：啟用狀態的用戶
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope：停用狀態的用戶
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope：依性別篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $gender
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope：依註冊時間範圍篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRegisteredBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope：有手機號碼的用戶
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHasPhone($query)
    {
        return $query->whereNotNull('phone')->where('phone', '!=', '');
    }

    /**
     * Scope：已驗證 Email 的用戶
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmailVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }


    /**
     * 檢查是否已驗證 Email
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * 標記 Email 為已驗證
     *
     * @return bool
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * 檢查是否為完全活躍會員（已啟用且已驗證）
     *
     * @return bool
     */
    public function isVerifiedAndActive(): bool
    {
        return $this->is_active && $this->hasVerifiedEmail();
    }


    /**
     * 更新登入記錄
     *
     * @return bool
     */
    public function updateLoginRecord(): bool
    {
        return $this->update([
            'last_login_at' => now(),
            'login_count' => $this->login_count + 1,
        ]);
    }

    /**
     * 取得性別顯示文字
     *
     * @return string
     */
    public function getGenderTextAttribute(): string
    {
        return match ($this->gender) {
            'male' => '男性',
            'female' => '女性',
            'other' => '其他',
            default => '未設定',
        };
    }

    /**
     * 關聯：用戶的社群帳號
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * 關聯：用戶的 Google 帳號
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function googleAccount()
    {
        return $this->hasOne(SocialAccount::class)->where('provider', 'google');
    }

    /**
     * 關聯：用戶的 LINE 帳號
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lineAccount()
    {
        return $this->hasOne(SocialAccount::class)->where('provider', 'line');
    }


    /**
     * Scope：篩選條件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $filters)
    {
        // 搜尋邏輯（姓名或Email）
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
            });
        });

        // 性別篩選
        $query->when($filters['gender'] ?? null, function ($query, $gender) {
            $query->byGender($gender);
        });

        // 註冊時間範圍篩選
        $query->when(
            ($filters['register_start_date'] ?? null) && ($filters['register_end_date'] ?? null),
            function ($query) use ($filters) {
                $query->registeredBetween($filters['register_start_date'], $filters['register_end_date']);
            }
        );

        // 也支援舊版參數名稱（向後相容）
        $query->when(
            ($filters['created_start'] ?? null) && ($filters['created_end'] ?? null),
            function ($query) use ($filters) {
                $query->registeredBetween($filters['created_start'], $filters['created_end']);
            }
        );

        // 城市篩選
        $query->when($filters['city_id'] ?? null, function ($query, $cityId) {
            $query->where('address->city_id', $cityId);
        });

        // 年齡範圍篩選
        $query->when($filters['age_min'] ?? null, function ($query, $ageMin) {
            $maxBirthdate = now()->subYears($ageMin)->endOfYear();
            $query->where('birthdate', '<=', $maxBirthdate);
        });

        $query->when($filters['age_max'] ?? null, function ($query, $ageMax) {
            $minBirthdate = now()->subYears($ageMax + 1)->startOfYear();
            $query->where('birthdate', '>=', $minBirthdate);
        });

        // 帳號狀態篩選
        $query->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
            $isActive = $filters['is_active'];
            // 轉換字串為 boolean
            if ($isActive === '1' || $isActive === 1 || $isActive === true) {
                $query->where('is_active', true);
            } elseif ($isActive === '0' || $isActive === 0 || $isActive === false) {
                $query->where('is_active', false);
            }
        });

        // 驗證狀態篩選
        $query->when($filters['verification_status'] ?? null, function ($query, $verificationStatus) {
            switch ($verificationStatus) {
                case '已完成':
                    // Email 已驗證且（不是第三方登入 或 資料已完整）
                    $query->whereNotNull('email_verified_at')
                          ->where(function ($q) {
                              $q->whereDoesntHave('socialAccounts')
                                ->orWhere('profile_completed', true);
                          });
                    break;
                case '待驗證':
                    // Email 未驗證且（不是第三方登入 或 資料已完整）
                    $query->whereNull('email_verified_at')
                          ->where(function ($q) {
                              $q->whereDoesntHave('socialAccounts')
                                ->orWhere('profile_completed', true);
                          });
                    break;
                case '待補充資料':
                    // 第三方登入且資料未完整
                    $query->whereHas('socialAccounts')
                          ->where('profile_completed', false);
                    break;
            }
        });

        return $query;
    }

}
