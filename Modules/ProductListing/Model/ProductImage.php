<?php

namespace Modules\ProductListing\Model;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'image_path',
        'type',
        'seq',
    ];

    protected $casts = [
        'seq' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
