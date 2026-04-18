<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationGroup extends Model
{
    protected $fillable = ['name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'variation_group_id');
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariationAttribute::class, 'variation_group_id');
    }
}
