<?php

namespace Modules\ProductListing\Model;

use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations, BaseModelTrait;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'type',
        'status',
        'price',
        'stock',
        'is_hot',
        'spec_combination_id',
        'description',
        'seq',
        'created_by',
        'updated_by',
    ];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'status'    => 'integer',
        'price'     => 'decimal:2',
        'stock'     => 'integer',
        'is_hot'    => 'boolean',
        'seq'       => 'integer',
    ];

    // ===== Scopes =====
    public function scopeOrdered($query)
    {
        return $query->orderBy('seq', 'asc')->orderBy('id', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ===== Relations =====
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('seq');
    }

    public function mainImage()
    {
        return $this->hasOne(ProductImage::class, 'product_id')
                    ->where('type', 'main')
                    ->orderBy('seq');
    }

    public function galleryImages()
    {
        return $this->hasMany(ProductImage::class, 'product_id')
                    ->where('type', 'gallery')
                    ->orderBy('seq');
    }

    public function skus()
    {
        return $this->hasMany(ProductSku::class, 'product_id');
    }

    public function specCombination()
    {
        return $this->belongsTo(
            \Modules\ProductSpecSetting\Model\SpecCombination::class,
            'spec_combination_id'
        );
    }

    public function categories()
    {
        return $this->belongsToMany(
            \Modules\FrontMenuSetting\Model\FrontMenu::class,
            'product_front_menu',
            'product_id',
            'front_menu_id'
        );
    }
}
