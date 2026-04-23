<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Article;

class Category extends Model
{
    use HasTranslations;
    use BaseModelTrait;

    protected $fillable = [
        'type', 'name', 'slug', 'description',
        'image', 'parent_id', 'level', 'seq', 'status',
        // RSS 來源對應欄位
        'source_code', 'source_provider'
    ];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'status' => 'boolean',
        'level' => 'integer',
        'seq' => 'integer'
    ];

    // 分類類型常量
    const TYPE_DRAMA = 'drama';
    const TYPE_PROGRAM = 'program';
    const TYPE_RADIO = 'radio';
    const TYPE_ARTICLE = 'article';
    const TYPE_NEWS = 'news';

    // 分類標題對應
    const TYPE_TITLES = [
        self::TYPE_DRAMA => '影音分類',
        self::TYPE_PROGRAM => '節目分類',
        self::TYPE_RADIO => '廣播分類',
        self::TYPE_ARTICLE => '新聞分類',
        self::TYPE_NEWS => '最新消息分類',
    ];

    // 關聯
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('seq');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id', 'id');
    }

    // 範圍查詢（使用 BaseModelTrait 的 scopeStatus）

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 根據分類類型取得中文標題（靜態方法）
     *
     * @param string $type 分類類型
     * @return string
     */
    public static function getTypeTitle($type)
    {
        return self::TYPE_TITLES[$type] ?? '未知分類';
    }

    /**
     * 取得當前分類的中文標題（實例方法）
     *
     * @return string
     */
    public function getTypeTitleAttribute()
    {
        return self::getTypeTitle($this->type);
    }

    /**
     * 取得所有分類類型選項
     *
     * @return array
     */
    public static function getTypeOptions()
    {
        return self::TYPE_TITLES;
    }

    /**
     * 取得所有分類類型常量
     *
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_DRAMA,
            self::TYPE_PROGRAM,
            self::TYPE_RADIO,
            self::TYPE_ARTICLE,
        ];
    }

    // 獲取所有子分類ID
    public function getAllChildrenIds()
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }
        return $ids;
    }

    // 圖片關聯
    public function coverImage()
    {
        return $this->morphOne(ImageManagement::class, 'attachable')
            ->where('image_type', 'cover');
    }

    /**
     * 操作紀錄標題 (event_title)
     * 如果是主分類，就顯示「主分類：XXX」，
     * 如果是子分類，就顯示「主分類：AAA／子分類：BBB」。
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn (string|null $value) => $value ?? self::getTypeTitle($this->type). '：' . $this->getTranslation('name', 'zh_TW'),
            set: fn (string $value) => $value,
        );
    }

}
