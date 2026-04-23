<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ListCity extends Model
{
    use HasTranslations;

    /**
     * 資料表名稱
     */
    protected $table = 'list_city';

    /**
     * 主鍵欄位
     */
    protected $primaryKey = 'sn';

    /**
     * 主鍵類型
     */
    protected $keyType = 'int';

    /**
     * 是否自動遞增
     */
    public $incrementing = true;

    /**
     * 是否使用時間戳
     */
    public $timestamps = false;

    /**
     * 可翻譯的欄位
     */
    public $translatable = ['title'];

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'title',
    ];

    /**
     * 欄位型別轉換
     */
    protected $casts = [
        'sn' => 'integer',
        'title' => 'array', // JSON 欄位轉為陣列
    ];

    /**
     * 關聯：城市擁有多個區域
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function areas()
    {
        return $this->hasMany(ListArea::class, 'city_sn', 'sn');
    }

    /**
     * 關聯：城市擁有多個用戶（透過 JSON 欄位）
     * 注意：這是一個虛擬關聯，實際查詢需要特殊處理
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'address->city_sn', 'sn');
    }

    /**
     * Scope：依名稱搜尋
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $title
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTitle($query, $title)
    {
        return $query->where('title', 'like', "%{$title}%");
    }

    /**
     * 取得城市名稱
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->title ?? '';
    }

    /**
     * 取得該城市的區域數量
     *
     * @return int
     */
    public function getAreaCount(): int
    {
        return $this->areas()->count();
    }
}