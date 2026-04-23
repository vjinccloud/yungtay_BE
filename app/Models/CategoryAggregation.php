<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryAggregation extends Model
{
    protected $table = 'category_aggregations';

    /**
     * 可批量賦值的屬性
     * 注意：不包含 unknown_age（性別和生日為必填欄位）
     */
    protected $fillable = [
        // 核心欄位
        'content_type',
        'category_id',
        'subcategory_id',  // 新增：子分類 ID（僅 drama/program 有值）

        // 時間維度
        'period_type',
        'period_date',
        
        // 基礎統計
        'total_views',
        'unique_views',
        'member_views',
        'guest_views',
        
        // 性別統計（只有男性和女性）
        'male_views',
        'female_views',
        
        // 年齡統計（7個區間，不含 unknown_age）
        'age_0_10',
        'age_11_20',
        'age_21_30',
        'age_31_40',
        'age_41_50',
        'age_51_60',
        'age_61_plus',
        
        // 男性年齡交叉統計（7個）
        'male_age_0_10',
        'male_age_11_20',
        'male_age_21_30',
        'male_age_31_40',
        'male_age_41_50',
        'male_age_51_60',
        'male_age_61_plus',
        
        // 女性年齡交叉統計（7個）
        'female_age_0_10',
        'female_age_11_20',
        'female_age_21_30',
        'female_age_31_40',
        'female_age_41_50',
        'female_age_51_60',
        'female_age_61_plus',
    ];

    /**
     * 屬性類型轉換
     */
    protected $casts = [
        'period_date' => 'date',
        'category_id' => 'integer',
        'subcategory_id' => 'integer',
        
        // 基礎統計（使用 bigint）
        'total_views' => 'integer',
        'unique_views' => 'integer',
        'member_views' => 'integer',
        'guest_views' => 'integer',
        
        // 性別統計
        'male_views' => 'integer',
        'female_views' => 'integer',
        
        // 年齡統計
        'age_0_10' => 'integer',
        'age_11_20' => 'integer',
        'age_21_30' => 'integer',
        'age_31_40' => 'integer',
        'age_41_50' => 'integer',
        'age_51_60' => 'integer',
        'age_61_plus' => 'integer',
        
        // 男性年齡交叉統計
        'male_age_0_10' => 'integer',
        'male_age_11_20' => 'integer',
        'male_age_21_30' => 'integer',
        'male_age_31_40' => 'integer',
        'male_age_41_50' => 'integer',
        'male_age_51_60' => 'integer',
        'male_age_61_plus' => 'integer',
        
        // 女性年齡交叉統計
        'female_age_0_10' => 'integer',
        'female_age_11_20' => 'integer',
        'female_age_21_30' => 'integer',
        'female_age_31_40' => 'integer',
        'female_age_41_50' => 'integer',
        'female_age_51_60' => 'integer',
        'female_age_61_plus' => 'integer',
    ];

    /**
     * 關聯：分類
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
