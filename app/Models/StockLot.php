<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLot extends Model
{
    protected $fillable = [
        'product_id',
        'store_id',
        'supplier_id',
        'supplier_order_id',
        'purchase_price',
        'quantity',
        'quantity_remaining',
        'expiry_date',
        'batch_number',
    ];

    public function product()       { return $this->belongsTo(Product::class); }
    public function store()         { return $this->belongsTo(Store::class); }
    public function supplier()      { return $this->belongsTo(Supplier::class); }
    public function supplierOrder() { return $this->belongsTo(SupplierOrder::class); }
}
