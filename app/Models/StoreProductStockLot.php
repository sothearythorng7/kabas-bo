<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreProductStockLot extends Model
{
    protected $fillable = ['store_id', 'product_id', 'quantity', 'purchase_price'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
