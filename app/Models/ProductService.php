<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ProductService extends Model
{
    use HasTranslations;

    protected $table = 'product_services';

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
     * 關聯：工廠（多對多）
     */
    public function factories()
    {
        return $this->belongsToMany(Factory::class, 'factory_product_service');
    }
}
