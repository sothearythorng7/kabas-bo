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
    public function getTotalStock(Store $store)
    {
        return $this->stockLots()->where('store_id', $store->id)->sum('quantity_remaining');
    }


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
    public function stockLots()
    {
        return $this->hasMany(StockLot::class);
    }
    public function images()     { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function primaryImage(){ return $this->hasOne(ProductImage::class)->where('is_primary', true); }
    public function resellerDeliveries()
    {
        return $this->belongsToMany(ResellerStockDelivery::class, 'reseller_stock_delivery_product')
                    ->withPivot('quantity', 'unit_price')
                    ->withTimestamps();
    }


    public function lots()
    {
        return $this->hasMany(StockLot::class);
    }

    public function removeStock(Store $store, int $quantity): bool
    {
        $lots = $this->stockLots()
            ->where('store_id', $store->id)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('created_at', 'asc') // FIFO
            ->get();

        $remaining = $quantity;

        foreach ($lots as $lot) {
            if ($remaining <= 0) break;

            $toDeduct = min($lot->quantity_remaining, $remaining);
            $lot->quantity_remaining -= $toDeduct;
            $lot->save();

            $remaining -= $toDeduct;
        }

        // Mettre à jour le stock global pour compatibilité pivot product_store
        $totalStock = $this->stockLots()
            ->where('store_id', $store->id)
            ->sum('quantity_remaining');

        $store->products()->syncWithoutDetaching([
            $this->id => ['stock_quantity' => $totalStock]
        ]);

        return $remaining === 0; // true si tout a été retiré, false sinon
    }
}
