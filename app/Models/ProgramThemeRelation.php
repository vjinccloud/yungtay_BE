<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BaseModelTrait;

class ProgramThemeRelation extends Model
{
    use HasFactory, BaseModelTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'program_theme_relations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'program_id',
        'theme_id',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'program_id' => 'integer',
        'theme_id' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 取得此關聯的節目
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * 取得此關聯的主題
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(ProgramTheme::class, 'theme_id');
    }
}