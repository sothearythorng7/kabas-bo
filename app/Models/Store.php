<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'opening_time',
        'closing_time',
        'type',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('stock_quantity')
            ->withTimestamps();
    }

    public function getTotalStock(Store $store)
    {
        return $this->stockLots()->where('store_id', $store->id)->sum('quantity_remaining');
    }


    // Nouveau scope pour filtrer les entrepôts
    public function scopeWarehouse($query)
    {
        return $query->where('type', 'warehouse');
    }

    // Idem pour les shops
    public function scopeShops($query)
    {
        return $query->where('type', 'shop');
    }

    public function stockLots()
    {
        return $this->hasMany(StockLot::class);
    }
}
