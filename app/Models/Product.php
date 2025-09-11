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
        'price_btob',
        'brand_id',
        'color',
        'size',
        'is_active',
        'is_best_seller',
        'is_resalable',
        'attributes',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'slugs' => 'array',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_resalable' => 'boolean',
    ];

    // Relation vers les stocks
    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    // Stock total pour un store donné
    public function getTotalStock(Store $store)
    {
        return $this->stockBatches()
            ->where('store_id', $store->id)
            ->sum('quantity');
    }

    // Stock total pour un reseller donné
    public function getResellerStock(Reseller $reseller)
    {
        return $this->stockBatches()
            ->where('reseller_id', $reseller->id)
            ->sum('quantity');
    }

    // Relations classiques
    public function brand()      { return $this->belongsTo(Brand::class); }
    public function categories() { return $this->belongsToMany(Category::class)->withTimestamps(); }
    public function suppliers()  {
        return $this->belongsToMany(Supplier::class)
            ->withPivot('purchase_price')
            ->withTimestamps();
    }
    public function stores() {
        return $this->belongsToMany(Store::class)
            ->withPivot('alert_stock_quantity')
            ->withTimestamps();
    }
    public function images()     { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function primaryImage(){ return $this->hasOne(ProductImage::class)->where('is_primary', true); }

    // Relation vers les livraisons
    public function resellerDeliveries()
    {
        return $this->belongsToMany(ResellerStockDelivery::class, 'reseller_stock_delivery_product')
                    ->withPivot('quantity', 'unit_price')
                    ->withTimestamps();
    }

    // Retrait de stock (FIFO) pour un store
    public function removeStock(Store $store, int $quantity): bool
    {
        $batches = $this->stockBatches()
            ->where('store_id', $store->id)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $toDeduct = min($batch->quantity, $remaining);
            $batch->quantity -= $toDeduct;
            $batch->save();

            $remaining -= $toDeduct;
        }

        // Ne plus toucher au pivot product_store
        return $remaining === 0;
    }


    // Booted pour gérer le price_btob
    protected static function booted()
    {
        static::creating(function ($product) {
            if ($product->is_resalable && is_null($product->price_btob)) {
                $product->price_btob = $product->price;
            }
        });

        static::updating(function ($product) {
            if ($product->is_resalable && is_null($product->price_btob)) {
                $product->price_btob = $product->price;
            }
        });
    }
}
