<?php

namespace Modules\ProductListing\Model;

use Illuminate\Database\Eloquent\Model;

class ProductSku extends Model
{
    protected $table = 'product_skus';

    protected $fillable = [
        'product_id',
        'spec_value_ids',
        'combination_label',
        'sku',
        'price',
        'stock',
        'status',
    ];

    protected $casts = [
        'spec_value_ids' => 'array',
        'price'          => 'decimal:2',
        'stock'          => 'integer',
        'status'         => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
