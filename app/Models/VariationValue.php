<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationValue extends Model
{
    protected $fillable = ['variation_type_id', 'value'];

    public function type()
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_variations')
                    ->withTimestamps();
    }
}
