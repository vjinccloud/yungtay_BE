<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Region extends Model
{
    use HasTranslations;

    protected $table = 'regions';

    protected $fillable = [
        'name',
        'sort',
        'is_enabled',
    ];

    public $translatable = ['name'];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * 排序 Scope
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort', 'asc')->orderBy('id', 'desc');
    }

    /**
     * 啟用 Scope
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 關聯：工廠
     */
    public function factories()
    {
        return $this->hasMany(Factory::class);
    }

    /**
     * 取得工廠數量
     */
    public function getFactoriesCountAttribute()
    {
        return $this->factories()->count();
    }
}
