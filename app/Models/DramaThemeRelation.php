<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BaseModelTrait;
class DramaThemeRelation extends Model
{
    use HasFactory, BaseModelTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'drama_theme_relations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'drama_id',
        'theme_id',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'drama_id' => 'integer',
        'theme_id' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 取得此關聯的影音
     */
    public function drama(): BelongsTo
    {
        return $this->belongsTo(Drama::class, 'drama_id');
    }

    /**
     * 取得此關聯的主題
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(DramaTheme::class, 'theme_id');
    }
}