<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'linked_product_id',
        'variation_type_id',
        'variation_value_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function linkedProduct()
    {
        return $this->belongsTo(Product::class, 'linked_product_id');
    }

    public function type()
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    public function value()
    {
        return $this->belongsTo(VariationValue::class, 'variation_value_id');
    }
}
