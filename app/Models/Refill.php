<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refill extends Model
{
    protected $fillable = ['supplier_id', 'destination_store_id', 'status'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function destinationStore()
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'refill_product')
            ->withPivot('purchase_price', 'quantity_received')
            ->withTimestamps();
    }

    public function getTotalQuantityReceivedAttribute()
    {
        return $this->products->sum('pivot.quantity_received');
    }
}
