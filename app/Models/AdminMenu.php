<?php

namespace App\Models;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class AdminMenu extends Model
{
    use HasFactory, BaseModelTrait;

    protected $table = 'admin_menu';

    protected $fillable = [
        'title',
        'parent_id',
        'type',
        'level',
        'url',
        'url_name',
        'icon_image',
        'status',
        'seq',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'type' => 'integer',
        'level' => 'integer',
        'status' => 'boolean',
        'seq' => 'integer',
    ];

    /**
     * 父層選單
     */
    public function parent()
    {
        return $this->belongsTo(AdminMenu::class, 'parent_id');
    }

    /**
     * 子選單
     */
    public function children()
    {
        return $this->hasMany(AdminMenu::class, 'parent_id');
    }

    /**
     * 排序 Scope
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('seq', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 頂層選單 Scope
     */
    public function scopeTopLevel($query)
    {
        return $query->where('parent_id', 0);
    }

    /**
     * 啟用的選單 Scope
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', true);
    }
}
