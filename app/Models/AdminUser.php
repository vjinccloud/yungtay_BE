<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
class AdminUser extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use HasFactory;
    use BaseModelTrait;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function image()
    {
        return $this->morphMany(ImageManagement::class, 'attachable')->where('image_type', '=', 'image');
    }

    public function scopeFilter($query, array $filters)
    {
        // 搜尋邏輯
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                // 新增角色查詢
                $subQuery->orWhereHas('roles', function ($roleQuery) use ($search) {
                    $roleQuery->where('name', 'like', "%{$search}%");
                });
            });
        });
        return $query;
    }

    public function getRoleIdAttribute()
    {
        // 假设 Admin 与 Role 是多对多关系
        return $this->roles->first()->id ?? null;
    }
    /**
     * Event Title Attribute
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ?? '管理員-'.$this->name,
        );
    }



}
