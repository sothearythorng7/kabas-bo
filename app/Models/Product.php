<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'ean',
        'name',
        'description',
        'slugs',
        'price',
        'brand_id',
        'color',
        'size',
        'is_active',
        'is_best_seller',
        'attributes',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'slugs' => 'array',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'is_best_seller' => 'boolean',
    ];

    public function brand()      { return $this->belongsTo(Brand::class); }
    public function categories() { return $this->belongsToMany(Category::class)->withTimestamps(); }
    public function suppliers()  {
        return $this->belongsToMany(Supplier::class)
            ->withPivot('purchase_price')
            ->withTimestamps();
    }
    public function stores() {
        return $this->belongsToMany(Store::class)
            ->withPivot('stock_quantity', 'alert_stock_quantity')
            ->withTimestamps();
    }
    public function images()     { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function primaryImage(){ return $this->hasOne(ProductImage::class)->where('is_primary', true); }
}
