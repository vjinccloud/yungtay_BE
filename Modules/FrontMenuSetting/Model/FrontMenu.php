<?php

namespace Modules\FrontMenuSetting\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * FrontMenu 前台選單 - Model
 * 
 * 層級式選單結構
 */
class FrontMenu extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'front_menus';

    protected $fillable = [
        'parent_id',
        'title',
        'level',
        'link_type',
        'link_url',
        'link_target',
        'icon',
        'seq',
        'status',
        'created_by',
        'updated_by',
    ];

    public $translatable = ['title'];

    protected $casts = [
        'status' => 'boolean',
        'parent_id' => 'integer',
        'level' => 'integer',
        'seq' => 'integer',
    ];

    /**
     * 排序 scope
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('seq', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 啟用的選單
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * 父層選單
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 子選單
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    /**
     * 啟用的子選單
     */
    public function activeChildren()
    {
        return $this->hasMany(self::class, 'parent_id')->active()->ordered();
    }
}
