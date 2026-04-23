<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ModuleDescription extends Model
{
    use HasFactory;
    use HasTranslations;
    use BaseModelTrait;
    
    protected $table = 'module_descriptions';
    
    protected $fillable = [
        'module_key',
        'meta_description',
        'meta_keywords',
        'created_by',
        'updated_by'
    ];
    
    /**
     * 多語言欄位
     */
    public $translatable = [
        'meta_description',
        'meta_keywords'
    ];
    
    /**
     * 預設載入關聯
     */
    protected $with = ['created_user', 'updated_user'];
    
    
    /**
     * 建立者關聯
     */
    public function created_user()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
    
    /**
     * 更新者關聯
     */
    public function updated_user()
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }
    
    /**
     * 取得模組名稱（從 config 檔案）
     */
    public function getModuleNameAttribute(): string
    {
        $moduleKeys = config('module_keys', []);
        return $moduleKeys[$this->module_key] ?? $this->module_key;
    }
    
    /**
     * 操作紀錄標題
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ?? '模組描述：' . $this->module_name,
        );
    }
}
