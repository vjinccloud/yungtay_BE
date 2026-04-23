<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class ListArea extends Model
{
    use HasTranslations;

    /**
     * 資料表名稱
     */
    protected $table = 'list_area';

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
        'city_sn',
        'title',
        'zipcode',
    ];

    /**
     * 欄位型別轉換
     */
    protected $casts = [
        'sn' => 'integer',
        'city_sn' => 'integer',
        'title' => 'array', // JSON 欄位轉為陣列
        'zipcode' => 'string',
    ];

    /**
     * 關聯：區域屬於某個城市
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(ListCity::class, 'city_sn', 'sn');
    }

    /**
     * 關聯：區域擁有多個用戶（透過 JSON 欄位）
     * 注意：這是一個虛擬關聯，實際查詢需要特殊處理
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'address->area_sn', 'sn');
    }

    /**
     * Scope：依城市篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $citySn
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCity($query, $citySn)
    {
        return $query->where('city_sn', $citySn);
    }

    /**
     * Scope：依郵遞區號搜尋
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $zipcode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByZipcode($query, $zipcode)
    {
        return $query->where('zipcode', $zipcode);
    }

    /**
     * Scope：依區域名稱搜尋
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
     * 取得區域名稱
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->title ?? '';
    }

    /**
     * 取得完整地址（城市 + 區域）
     *
     * @return string
     */
    public function getFullAddress(): string
    {
        return ($this->city?->title ?? '') . $this->title;
    }

    /**
     * 取得郵遞區號
     *
     * @return string
     */
    public function getZipcode(): string
    {
        return $this->zipcode ?? '';
    }
}