<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationValue extends Model
{
    protected $fillable = ['variation_type_id', 'value', 'color_hex', 'audit_decision', 'audit_decided_at'];

    protected $casts = [
        'audit_decision' => 'array',
        'audit_decided_at' => 'datetime',
    ];

    public function type()
    {
        return $this->belongsTo(VariationType::class, 'variation_type_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_variations')
                    ->withTimestamps();
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariationAttribute::class, 'variation_value_id');
    }
}
