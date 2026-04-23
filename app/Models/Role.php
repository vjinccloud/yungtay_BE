<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\BaseModelTrait;

class Role extends SpatieRole
{
    use HasFactory;
    use BaseModelTrait;
    protected $guard_name = 'admin';
    protected $fillable = [
        'name', 'guard_name','description'
    ];
    protected $with = ['permissions'];
    
   /**
     * Undocumented function
     *
     * @return Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) =>  $value ?? '角色權限-'.$this->name,
        );
    }

    public function scopeFilter($query, array $filters)
    {
        // 搜尋邏輯
        $query->when($filters['search'] ?? null, function ($query, $search) { 
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                         ->orWhere('description', 'like', "%{$search}%");
            }); 
        });
        return $query;
    }

    
    /**
     * 操作紀錄類型 (event_type)
     * 例如：Add主分類、Edit子分類
     */
    protected function eventType(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->wasRecentlyCreated ? 'Add' : 'Edit')
        );
    }
}
