<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    protected $fillable = [
        'product_id',
        'store_id',
        'supplier_order_id',
        'purchase_price',
        'quantity_initial',
        'quantity_remaining',
        'entry_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplierOrder()
    {
        return $this->belongsTo(SupplierOrder::class);
    }
}